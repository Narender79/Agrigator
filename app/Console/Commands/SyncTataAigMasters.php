<?php

namespace App\Console\Commands;

use App\Models\Insurance\Insurer;
use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use App\Services\TataAigMasterService;
use Illuminate\Console\Command;

class SyncTataAigMasters extends Command
{
    protected $signature   = 'sync:tata-aig-masters';
    protected $description = 'Fetch vehicle masters from Tata AIG and map them with canonical Droom database records.';

    protected TataAigMasterService $service;

    // ── In-memory indexes ────────────────────────────────────────────────────
    /** @var array<string, object>  normalized_name -> Make */
    private array $makeIndex = [];
    /** @var array<string, object>  "make_id:normalized_model" -> VehicleModel */
    private array $modelIndex = [];
    /** @var array<string, object>  "make_id:model_id:normalized_trim" -> Trim */
    private array $trimIndex = [];

    // Flat lists for similarity search
    private array $makeKeys  = [];   // [normalized_key => Make]
    private array $modelKeys = [];   // ["make_id:key" => VehicleModel]
    private array $trimKeys  = [];   // ["make_id:model_id:key" => Trim]

    public function __construct(TataAigMasterService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    // ════════════════════════════════════════════════════════════════════════
    // MAIN
    // ════════════════════════════════════════════════════════════════════════

    public function handle(): int
    {
        $insurer = Insurer::firstOrCreate(
            ['company_name' => 'TATA AIG GENERAL INSURANCE'],
            ['status' => 'active', 'regulatory_reg_no' => '108']
        );

        // ── STEP 1 : Load Droom catalog ──────────────────────────────────────
        $this->info('Loading Droom catalog into memory…');
        $this->buildIndexes();

        // ── STEP 2 : Fetch Tata AIG catalog ─────────────────────────────────
        $tataMakes = $this->service->fetchMakes();
        if (empty($tataMakes)) {
            $this->error('No makes returned from Tata AIG API.');
            return Command::FAILURE;
        }

        $makeMappings  = [];
        $modelMappings = [];
        $trimMappings  = [];
        $mismatches    = [];

        // ── STEP 3 : Map ─────────────────────────────────────────────────────
        foreach ($tataMakes as $tataMake) {
            $tataMakeCode = (int) $tataMake['code'];
            $tataMakeName = trim($tataMake['name']);

            $droomMake = $this->resolveMake($tataMakeCode, $tataMakeName);

            if (!$droomMake) {
                $mismatches[] = [
                    'level'     => 'make',
                    'tata_code' => $tataMakeCode,
                    'tata_name' => $tataMakeName,
                ];
                $this->warn("[MAKE MISS] '{$tataMakeName}' (code: {$tataMakeCode})");
                continue;
            }

            $makeKey = "{$insurer->id}-{$droomMake->id}";
            $this->info($tataMakeCode . " , " . $droomMake->id);
            $makeMappings[$makeKey] ??= [
                'insurer_id'        => $insurer->id,
                'dr_make_id'        => $droomMake->id,
                'insurer_make_id'   => $tataMakeCode,
                'insurer_make_name' => $tataMakeName,
                'status'            => 'active',
            ];

            $tataModels = $this->service->fetchModels($tataMakeCode, $tataMakeName);

            foreach ($tataModels as $tataModel) {
                $tataModelCode = (int) $tataModel['code'];
                $tataModelName = trim($tataModel['name']);

                $droomModel = $this->resolveModel($tataModelCode, $tataModelName, $droomMake->id);

                if (!$droomModel) {
                    $mismatches[] = [
                        'level'     => 'model',
                        'tata_code' => $tataModelCode,
                        'tata_name' => $tataModelName,
                        'make'      => $tataMakeName,
                    ];
                    continue;
                }

                $modelKey = "{$insurer->id}-{$droomModel->id}";

                $this->info($tataModelCode . " , " . $droomMake->id);
                $modelMappings[$modelKey] ??= [
                    'insurer_id'         => $insurer->id,
                    'category_id'        => $droomModel->category_id,
                    'dr_make_id'         => $droomMake->id,
                    'dr_model_id'        => $droomModel->id,
                    'insurer_model_id'   => $tataModelCode,
                    'insurer_model_name' => $tataModelName,
                    'status'             => 'active',
                ];

                $tataVariants = $this->service->fetchVariants($tataModelCode, $tataModelName);

                foreach ($tataVariants as $tataVariant) {
                    $tataVariantCode = (int) $tataVariant['code'];
                    $tataVariantName = trim($tataVariant['name']);

                    $droomTrim = $this->resolveTrim(
                        $tataVariantCode,
                        $tataVariantName,
                        $droomMake->id,
                        $droomModel->id
                    );

                    if (!$droomTrim) {
                        $mismatches[] = [
                            'level'     => 'trim',
                            'tata_code' => $tataVariantCode,
                            'tata_name' => $tataVariantName,
                            'make'      => $tataMakeName,
                            'model'     => $tataModelName,
                        ];
                        continue;
                    }

                    $trimKey = "{$insurer->id}-{$droomTrim->id}";
                    $this->info($tataVariantCode . " , " . $droomMake->id);
                    $trimMappings[$trimKey] ??= [
                        'insurer_id'        => $insurer->id,
                        'dr_make_id'        => $droomMake->id,
                        'dr_model_id'       => $droomModel->id,
                        'dr_trim_id'        => $droomTrim->id,
                        'insurer_trim_id'   => $tataVariantCode,
                        'insurer_trim_name' => $tataVariantName,
                        'status'            => 'active',
                    ];
                }
            }
        }

        // ── STEP 4 : Batch upsert ────────────────────────────────────────────
        $this->saveChunked(
            'Make Mappings',
            $makeMappings,
            MakeMapping::class,
            ['insurer_id', 'dr_make_id'],
            ['insurer_make_id', 'insurer_make_name', 'status']
        );

        $this->saveChunked(
            'Model Mappings',
            $modelMappings,
            ModelMapping::class,
            ['insurer_id', 'dr_model_id'],
            ['category_id', 'dr_make_id', 'insurer_model_id', 'insurer_model_name', 'status']
        );

        $this->saveChunked(
            'Trim Mappings',
            $trimMappings,
            TrimMapping::class,
            ['insurer_id', 'dr_trim_id'],
            ['dr_make_id', 'dr_model_id', 'insurer_trim_id', 'insurer_trim_name', 'status']
        );

        // ── Mismatch log ─────────────────────────────────────────────────────
        if (!empty($mismatches)) {
            $byLevel = collect($mismatches)->groupBy('level');
            $this->warn(sprintf(
                'Mismatches — Makes: %d | Models: %d | Trims: %d',
                $byLevel->get('make', collect())->count(),
                $byLevel->get('model', collect())->count(),
                $byLevel->get('trim', collect())->count(),
            ));
            $path = storage_path('logs/tata_aig_mismatches_' . now()->format('Y_m_d_His') . '.json');
            file_put_contents($path, json_encode($mismatches, JSON_PRETTY_PRINT));
            $this->line("Mismatch log: {$path}");
        }

        $this->info(sprintf(
            'Done. Mapped %d makes | %d models | %d trims.',
            count($makeMappings),
            count($modelMappings),
            count($trimMappings)
        ));

        return Command::SUCCESS;
    }

    // ════════════════════════════════════════════════════════════════════════
    // INDEX BUILDERS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Build three fast-lookup indexes from the full Droom catalog.
     * Each entry is keyed by EVERY normalized variant we can derive so
     * a single pass resolves the vast majority of Tata AIG names.
     */
    private function buildIndexes(): void
    {
        // ── Makes ────────────────────────────────────────────────────────────
        Make::all()->each(function ($make) {
            foreach ($this->nameVariants($make->make_name) as $v) {
                $this->makeIndex[$v] ??= $make;
            }
        });
        $this->makeKeys = $this->makeIndex;   // alias for similarity search

        // ── Models ───────────────────────────────────────────────────────────
        VehicleModel::all()->each(function ($model) {
            foreach ($this->nameVariants($model->model_name) as $v) {
                $key = "{$model->make_id}:{$v}";
                $this->modelIndex[$key] ??= $model;
            }
        });
        $this->modelKeys = $this->modelIndex;

        // ── Trims ────────────────────────────────────────────────────────────
        Trim::all()->each(function ($trim) {
            foreach ($this->nameVariants($trim->trim_name) as $v) {
                $key = "{$trim->make_id}:{$trim->model_id}:{$v}";
                $this->trimIndex[$key] ??= $trim;
            }
        });
        $this->trimKeys = $this->trimIndex;

        $this->info(sprintf(
            'Indexes ready — makes: %d keys | models: %d keys | trims: %d keys',
            count($this->makeIndex),
            count($this->modelIndex),
            count($this->trimIndex)
        ));
    }

    // ════════════════════════════════════════════════════════════════════════
    // RESOLUTION  (make → model → trim)
    // ════════════════════════════════════════════════════════════════════════

    private function resolveMake(int $tataCode, string $tataName): ?object
    {
        // 1. Hard-coded overrides (highest priority, human-verified)
        if ($id = ($this->makeOverrides()[$tataCode] ?? null)) {
            return Make::find($id);
        }

        // 2. Index lookup across all name variants
        foreach ($this->nameVariants($tataName) as $v) {
            if (isset($this->makeIndex[$v])) {
                return $this->makeIndex[$v];
            }
        }

        // 3. Similarity search with generous threshold for makes
        return $this->similaritySearch($tataName, $this->makeKeys, 4, 'make');
    }

    private function resolveModel(int $tataCode, string $tataName, int $droomMakeId): ?object
    {
        // 1. Hard-coded overrides
        if ($id = ($this->modelOverrides()[$tataCode] ?? null)) {
            return VehicleModel::find($id);
        }

        // 2. Index lookup
        foreach ($this->nameVariants($tataName) as $v) {
            $key = "{$droomMakeId}:{$v}";
            if (isset($this->modelIndex[$key])) {
                return $this->modelIndex[$key];
            }
        }

        // 3. Similarity search scoped to this make
        $prefix = "{$droomMakeId}:";
        $scoped = array_filter(
            $this->modelKeys,
            fn($k) => str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY
        );
        return $this->similaritySearch($tataName, $scoped, 3, 'model', $prefix);
    }

    private function resolveTrim(int $tataCode, string $tataName, int $droomMakeId, int $droomModelId): ?object
    {
        // 1. Hard-coded overrides
        if ($id = ($this->trimOverrides()[$tataCode] ?? null)) {
            return Trim::find($id);
        }

        // 2. Index lookup
        foreach ($this->nameVariants($tataName) as $v) {
            $key = "{$droomMakeId}:{$droomModelId}:{$v}";
            if (isset($this->trimIndex[$key])) {
                return $this->trimIndex[$key];
            }
        }

        // 3. Similarity search scoped to this model
        $prefix = "{$droomMakeId}:{$droomModelId}:";
        $scoped = array_filter(
            $this->trimKeys,
            fn($k) => str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY
        );
        return $this->similaritySearch($tataName, $scoped, 3, 'trim', $prefix);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SIMILARITY SEARCH  (last-resort fuzzy)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Find the best match in $indexSlice using multiple string-distance
     * metrics. The $prefix is stripped from keys before comparison so
     * we only diff on the actual name part.
     *
     * Strategy order (cheapest → most robust):
     *   a) Levenshtein distance
     *   b) Similar-text percentage
     *   c) Longest-common-subsequence ratio
     */
    private function similaritySearch(
        string $tataNeedle,
        array  $indexSlice,
        int    $maxLevenshtein,
        string $level = '',
        string $stripPrefix = ''
    ): ?object {
        $needle     = $this->normalize($tataNeedle);
        $bestEntity = null;
        $bestScore  = PHP_INT_MAX;   // lower levenshtein = better

        foreach ($indexSlice as $key => $entity) {
            $haystack = $stripPrefix ? substr($key, strlen($stripPrefix)) : $key;

            // ── a) Levenshtein ──
            $lev = levenshtein($needle, $haystack);
            if ($lev === 0) return $entity;               // perfect match early exit
            if ($lev <= $maxLevenshtein && $lev < $bestScore) {
                $bestScore  = $lev;
                $bestEntity = $entity;
            }

            // ── b) similar_text (catches transpositions / partial matches) ──
            similar_text($needle, $haystack, $pct);
            // Convert pct to a pseudo-distance so we can compare uniformly
            $pctDist = (int) ((100 - $pct) / 10);   // 90 % similarity → dist = 1
            if ($pct >= 80 && $pctDist < $bestScore) {
                $bestScore  = $pctDist;
                $bestEntity = $entity;
            }
        }

        return $bestEntity;
    }

    // ════════════════════════════════════════════════════════════════════════
    // NAME VARIANT GENERATION
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Produce every plausible normalization of a vehicle name.
     * The more variants we index, the fewer we need fuzzy-search for.
     */
    private function nameVariants(string $raw): array
    {
        $base = $this->normalize($raw);

        $variants = [
            $base,
            // Remove all spaces
            str_replace(' ', '', $base),
            // Remove punctuation
            preg_replace('/[^a-z0-9 ]/', '', $base),
            // Token-sorted (handles "hero honda" vs "honda hero")
            implode(' ', collect(explode(' ', $base))->sort()->values()->all()),
        ];

        // ── Domain-specific substitutions ───────────────────────────────────
        // Add expansions/contractions commonly seen between Tata AIG & Droom

        $substitutions = [
            // Makes
            'hero motocorp'      => 'hero',
            'hero moto corp'     => 'hero',
            'hero honda'         => 'hero honda',
            'escorts yamaha'     => 'yamaha',
            'cf moto'            => 'cfmoto',
            'cfmoto'             => 'cf moto',
            'dsk mo'             => 'dsk',
            'kinetic green'      => 'kinetic',
            'greenuber ev'       => 'greenuber',

            // Common model suffixes / BS rating tags
            ' bs6'               => '',
            ' bs 6'              => '',
            ' bs4'               => '',
            ' bs 4'              => '',
            ' bsvi'              => '',
            ' bsiv'              => '',
            ' bsiii'             => '',

            // Common abbreviation expansions in trims
            ' fi'                => ' fuel injection',
            ' fuel injection'    => ' fi',
            ' abs'               => '',
            ' cbs'               => '',
            ' es'                => ' electric start',
            ' electric start'    => ' es',
            ' std'               => ' standard',
            ' standard'          => ' std',
            ' dlx'               => ' deluxe',
            ' deluxe'            => ' dlx',
            ' disc'              => ' disk',
            ' disk'              => ' disc',
        ];

        foreach ($substitutions as $from => $to) {
            if (str_contains($base, $from)) {
                $substituted = str_replace($from, $to, $base);
                $variants[]  = $substituted;
                // Also add space-stripped and punct-stripped versions of subs
                $variants[]  = str_replace(' ', '', $substituted);
                $variants[]  = preg_replace('/[^a-z0-9 ]/', '', $substituted);
            }
        }

        // Strip trailing "motors", "electric", "mobility", "ev" and re-index
        $stripped = preg_replace('/\b(motors?|electric|mobility|ev|auto)\b/', '', $base);
        $stripped = preg_replace('/\s+/', ' ', trim($stripped));
        if ($stripped && $stripped !== $base) {
            $variants[] = $stripped;
            $variants[] = str_replace(' ', '', $stripped);
        }

        // For names like "BAJAJ RE" try both "bajaj re" and "bajaj"
        $words = explode(' ', $base);
        if (count($words) > 1) {
            $variants[] = $words[0];                        // first word only
            $variants[] = implode(' ', array_slice($words, 0, 2)); // first two words
        }

        return array_unique(array_filter($variants));
    }

    private function normalize(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }

    // ════════════════════════════════════════════════════════════════════════
    // HARD-CODED OVERRIDES  (Tata code → Droom ID)
    // ════════════════════════════════════════════════════════════════════════
    // These are populated by reviewing the mismatch JSON after the first run.
    // Once a mapping is verified, add it here so it bypasses fuzzy logic.

    private function makeOverrides(): array
    {
        return [
            // tata_code => droom_make_id
            959  => null,   // HERO MOTOCORP  — fill after checking Droom DB
            1182 => null,   // HERO MOTOCORP (duplicate tata entry)
            952  => null,   // ESCORTS YAMAHA → map to Yamaha make_id
            949  => null,   // DSK MO
            // Add more as you review the mismatch log
        ];
    }

    private function modelOverrides(): array
    {
        return [
            // tata_model_code => droom_model_id
        ];
    }

    private function trimOverrides(): array
    {
        return [
            // tata_trim_code => droom_trim_id
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private function saveChunked(string $label, array $rows, string $model, array $unique, array $update): void
    {
        $this->info("Saving {$label}…");
        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            $model::upsert($chunk, $unique, $update);
        }
        $this->line("  → {$label}: " . count($rows) . ' rows');
    }
}
