<?php

namespace App\Console\Commands;

use App\Models\Insurance\Insurer;
use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use App\Services\GeminiVehicleMatcher;
use Illuminate\Console\Command;

class SyncInsurerMasters extends Command
{
    protected $signature   = 'sync:insurer-masters';
    protected $description = 'Sync vehicle masters from Excel downloads and map them with Droom using Gemini fallback.';

    private GeminiVehicleMatcher $gemini;

    // In-memory indexes
    private array $makeIndex = [];
    private array $modelIndex = [];
    private array $trimIndex = [];

    private array $makeKeys  = [];
    private array $modelKeys = [];
    private array $trimKeys  = [];

    public function __construct(GeminiVehicleMatcher $gemini)
    {
        parent::__construct();
        $this->gemini = $gemini;
    }

    public function handle(): int
    {
        $this->warn('══════════════════════════════════════════════════');
        $this->warn('           TATA AIG SYNC MASTER (AI VERSION)      ');
        $this->warn('══════════════════════════════════════════════════');

        $insurer = Insurer::firstOrCreate(
            ['company_name' => 'TATA AIG GENERAL INSURANCE'],
            ['status' => 'active', 'regulatory_reg_no' => '108']
        );


        // ── STEP 2: Load CSV Data in Pure PHP ────────────────────────────────
        $this->warn('Step 2: Parsing CSV files from Downloads...');
        $downloads = '/home/narenderkalaliya/Downloads';
        $csvFiles = [
            '2w' => "{$downloads}/2W.csv",
            '4w' => "{$downloads}/4W.csv",
        ];

        $records = [];

        foreach ($csvFiles as $type => $filePath) {
            if (!file_exists($filePath)) {
                $this->error("CSV file not found: {$filePath}");
                continue;
            }

            if (($handle = fopen($filePath, 'r')) !== false) {
                $headers = fgetcsv($handle, 1000, ',');
                $headers = array_map('trim', $headers);

                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    // Filter out short or incomplete rows
                    if (count($row) < count($headers)) {
                        continue;
                    }
                    $rowDict = array_combine($headers, $row);
                    if ($rowDict && !empty($rowDict['num_manufacturercode'])) {
                        $records[] = [
                            'make_code'  => (int) $rowDict['num_manufacturercode'],
                            'make_name'  => trim($rowDict['txt_manufacturername']),
                            'model_code' => (int) $rowDict['num_model_code'],
                            'model_name' => trim($rowDict['txt_model_name']),
                            'trim_code'  => (int) $rowDict['num_model_variant_code'],
                            'trim_name'  => trim($rowDict['txt_model_variant']),
                            'type'       => $type,
                        ];
                    }
                }
                fclose($handle);
            }
        }

        $totalRows = count($records);
        $this->info("Parsed {$totalRows} total master rows from CSV files.");

        // ── STEP 3: Load Droom Catalog ──────────────────────────────────────
        $this->warn('Step 3: Loading Droom catalog into memory...');
        $this->buildIndexes();

        // ── STEP 4: Process Mapping ──────────────────────────────────────────
        $this->warn('Step 4: Beginning vehicle matching process...');

        $mappedMakes = [];
        $mappedModels = [];
        $mappedTrims = [];

        $unresolvedMakes = [];
        $unresolvedModels = [];
        $unresolvedTrims = [];

        // Collect all distinct inputs
        $distinctMakes = [];
        $distinctModels = [];

        foreach ($records as $row) {
            $distinctMakes[$row['make_code']] = $row['make_name'];
            $modelKey = "{$row['make_code']}-{$row['model_code']}";
            $distinctModels[$modelKey] = [
                'make_code' => $row['make_code'],
                'make_name' => $row['make_name'],
                'model_code' => $row['model_code'],
                'model_name' => $row['model_name']
            ];
        }

        // --- MATCH MAKES ---
        $this->info('Matching Makes...');
        foreach ($distinctMakes as $code => $name) {
            $droomMake = $this->resolveMake((int)$code, $name);
            if ($droomMake) {
                $mappedMakes[$code] = $droomMake->id;
                $this->line("[Make] '{$name}' (code: {$code}) matched successfully with Droom Make ID: {$droomMake->id}");
            } else {
                $unresolvedMakes[] = ['code' => $code, 'name' => $name];
                $this->warn("[Make] '{$name}' (code: {$code}) unresolved ➔ Adding to Gemini AI fallback...");
            }
        }

        if (!empty($unresolvedMakes)) {
            $this->warn('Running Gemini AI Fallback for ' . count($unresolvedMakes) . ' Makes...');
            $droomMakesList = Make::all(['id', 'make_name'])->toArray();
            $aiMatches = $this->gemini->resolveMakes($unresolvedMakes, $droomMakesList);
            foreach ($aiMatches as $code => $id) {
                if ($id) {
                    $mappedMakes[$code] = $id;
                    $this->info("[AI MATCH] Make Code {$code} mapped to Droom Make ID: {$id}");
                }
            }
        }

        // --- MATCH MODELS ---
        $this->info('Matching Models...');
        foreach ($distinctModels as $key => $m) {
            $makeId = $mappedMakes[$m['make_code']] ?? null;
            if (!$makeId) continue;

            $droomModel = $this->resolveModel((int)$m['model_code'], $m['model_name'], $makeId);
            if ($droomModel) {
                $mappedModels[$m['model_code']] = $droomModel->id;
                $this->line("[Model] '{$m['model_name']}' matched successfully with Droom Model ID: {$droomModel->id}");
            } else {
                $unresolvedModels[] = [
                    'code' => $m['model_code'],
                    'name' => $m['model_name'],
                    'make' => $m['make_name']
                ];
                $this->warn("[Model] '{$m['model_name']}' unresolved ➔ Adding to Gemini AI fallback...");
            }
        }

        if (!empty($unresolvedModels)) {
            $this->warn('Running Gemini AI Fallback for ' . count($unresolvedModels) . ' Models...');
            $droomModelsList = VehicleModel::all(['id', 'model_name', 'make_id'])->toArray();
            $aiMatches = $this->gemini->resolveModels($unresolvedModels, $droomModelsList);
            foreach ($aiMatches as $code => $id) {
                if ($id) {
                    $mappedModels[$code] = $id;
                    $this->info("[AI MATCH] Model Code {$code} mapped to Droom Model ID: {$id}");
                }
            }
        }

        // --- MATCH TRIMS ---
        $this->info('Matching Trims...');
        $totalTrims = count($records);
        $processedTrims = 0;

        foreach ($records as $row) {
            $processedTrims++;
            $makeId = $mappedMakes[$row['make_code']] ?? null;
            $modelId = $mappedModels[$row['model_code']] ?? null;
            if (!$makeId || !$modelId) continue;

            $droomTrim = $this->resolveTrim(
                (int)$row['trim_code'],
                $row['trim_name'],
                $makeId,
                $modelId
            );

            if ($droomTrim) {
                $mappedTrims[$row['trim_code']] = $droomTrim->id;
            } else {
                $unresolvedTrims[] = [
                    'code' => $row['trim_code'],
                    'name' => $row['trim_name'],
                    'make' => $row['make_name'],
                    'model' => $row['model_name']
                ];
                $this->warn("[Trim] '{$row['trim_name']}' unresolved ➔ Adding to Gemini AI fallback...");
            }

            if ($processedTrims % 500 === 0) {
                $this->line("Processed {$processedTrims} / {$totalTrims} Trims...");
            }
        }

        if (!empty($unresolvedTrims)) {
            $this->warn('Running Gemini AI Fallback for ' . count($unresolvedTrims) . ' Trims...');
            $droomTrimsList = Trim::all(['id', 'trim_name', 'model_id'])->toArray();
            $aiMatches = $this->gemini->resolveTrims($unresolvedTrims, $droomTrimsList);
            foreach ($aiMatches as $code => $id) {
                if ($id) {
                    $mappedTrims[$code] = $id;
                }
            }
            $this->info("AI Fallback processing complete for Trims.");
        }

        // ── STEP 5: Prepare & Insert Mappings ──────────────────────────────
        $this->warn('Step 5: Storing finalized mappings inside SQL Database...');

        $finalMakes = [];
        $finalModels = [];
        $finalTrims = [];

        foreach ($records as $row) {
            $makeId = $mappedMakes[$row['make_code']] ?? null;
            $modelId = $mappedModels[$row['model_code']] ?? null;
            $trimId = $mappedTrims[$row['trim_code']] ?? null;

            if ($makeId) {
                $finalMakes["{$insurer->id}-{$makeId}"] = [
                    'insurer_id'        => $insurer->id,
                    'dr_make_id'        => $makeId,
                    'insurer_make_id'   => $row['make_code'],
                    'insurer_make_name' => $row['make_name'],
                    'status'            => 'active',
                ];
            }

            if ($makeId && $modelId) {
                $finalModels["{$insurer->id}-{$modelId}"] = [
                    'insurer_id'         => $insurer->id,
                    'category_id'        => $row['type'] === '2w' ? 491 : 81,
                    'dr_make_id'         => $makeId,
                    'dr_model_id'        => $modelId,
                    'insurer_model_id'   => $row['model_code'],
                    'insurer_model_name' => $row['model_name'],
                    'status'             => 'active',
                ];
            }

            if ($makeId && $modelId && $trimId) {
                $finalTrims["{$insurer->id}-{$trimId}"] = [
                    'insurer_id'        => $insurer->id,
                    'dr_make_id'        => $makeId,
                    'dr_model_id'       => $modelId,
                    'dr_trim_id'        => $trimId,
                    'insurer_trim_id'   => $row['trim_code'],
                    'insurer_trim_name' => $row['trim_name'],
                    'status'            => 'active',
                ];
            }
        }

        $this->saveChunked('Make Mappings', $finalMakes, MakeMapping::class, ['insurer_id', 'dr_make_id'], ['insurer_make_id', 'insurer_make_name', 'status']);
        $this->saveChunked('Model Mappings', $finalModels, ModelMapping::class, ['insurer_id', 'dr_model_id'], ['category_id', 'dr_make_id', 'insurer_model_id', 'insurer_model_name', 'status']);
        $this->saveChunked('Trim Mappings', $finalTrims, TrimMapping::class, ['insurer_id', 'dr_trim_id'], ['dr_make_id', 'dr_model_id', 'insurer_trim_id', 'insurer_trim_name', 'status']);

        $this->info('--- Synchronization Completed Successfully! ---');
        $this->info("Final Mapped Count ➔ Makes: " . count($finalMakes) . " | Models: " . count($finalModels) . " | Trims: " . count($finalTrims));

        return Command::SUCCESS;
    }

    private function buildIndexes(): void
    {
        Make::all()->each(function ($make) {
            foreach ($this->nameVariants($make->make_name) as $v) {
                $this->makeIndex[$v] ??= $make;
            }
        });
        $this->makeKeys = $this->makeIndex;

        VehicleModel::all()->each(function ($model) {
            foreach ($this->nameVariants($model->model_name) as $v) {
                $key = "{$model->make_id}:{$v}";
                $this->modelIndex[$key] ??= $model;
            }
        });
        $this->modelKeys = $this->modelIndex;

        Trim::all()->each(function ($trim) {
            foreach ($this->nameVariants($trim->trim_name) as $v) {
                $key = "{$trim->make_id}:{$trim->model_id}:{$v}";
                $this->trimIndex[$key] ??= $trim;
            }
        });
        $this->trimKeys = $this->trimIndex;
    }

    private function resolveMake(int $tataCode, string $tataName): ?object
    {
        if ($id = ($this->makeOverrides()[$tataCode] ?? null)) {
            return Make::find($id);
        }

        foreach ($this->nameVariants($tataName) as $v) {
            if (isset($this->makeIndex[$v])) {
                return $this->makeIndex[$v];
            }
        }

        return $this->similaritySearch($tataName, $this->makeKeys, 4);
    }

    private function resolveModel(int $tataCode, string $tataName, int $droomMakeId): ?object
    {
        if ($id = ($this->modelOverrides()[$tataCode] ?? null)) {
            return VehicleModel::find($id);
        }

        foreach ($this->nameVariants($tataName) as $v) {
            $key = "{$droomMakeId}:{$v}";
            if (isset($this->modelIndex[$key])) {
                return $this->modelIndex[$key];
            }
        }

        $prefix = "{$droomMakeId}:";
        $scoped = array_filter(
            $this->modelKeys,
            fn($k) => str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY
        );
        return $this->similaritySearch($tataName, $scoped, 3, $prefix);
    }

    private function resolveTrim(int $tataCode, string $tataName, int $droomMakeId, int $droomModelId): ?object
    {
        if ($id = ($this->trimOverrides()[$tataCode] ?? null)) {
            return Trim::find($id);
        }

        foreach ($this->nameVariants($tataName) as $v) {
            $key = "{$droomMakeId}:{$droomModelId}:{$v}";
            if (isset($this->trimIndex[$key])) {
                return $this->trimIndex[$key];
            }
        }

        $prefix = "{$droomMakeId}:{$droomModelId}:";
        $scoped = array_filter(
            $this->trimKeys,
            fn($k) => str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY
        );
        return $this->similaritySearch($tataName, $scoped, 3, $prefix);
    }

    private function similaritySearch(
        string $tataNeedle,
        array  $indexSlice,
        int    $maxLevenshtein,
        string $stripPrefix = ''
    ): ?object {
        $needle     = $this->normalize($tataNeedle);
        $bestEntity = null;
        $bestScore  = PHP_INT_MAX;

        foreach ($indexSlice as $key => $entity) {
            $haystack = $stripPrefix ? substr($key, strlen($stripPrefix)) : $key;

            $lev = levenshtein($needle, $haystack);
            if ($lev === 0) return $entity;
            if ($lev <= $maxLevenshtein && $lev < $bestScore) {
                $bestScore  = $lev;
                $bestEntity = $entity;
            }

            similar_text($needle, $haystack, $pct);
            $pctDist = (int) ((100 - $pct) / 10);
            if ($pct >= 80 && $pctDist < $bestScore) {
                $bestScore  = $pctDist;
                $bestEntity = $entity;
            }
        }

        return $bestEntity;
    }

    private function nameVariants(string $raw): array
    {
        $base = $this->normalize($raw);
        $variants = [
            $base,
            str_replace(' ', '', $base),
            preg_replace('/[^a-z0-9 ]/', '', $base),
        ];

        $substitutions = [
            // Manufacturer names
            'hero motocorp'  => 'hero',
            'hero moto corp' => 'hero',
            'escorts yamaha' => 'yamaha',
            'cf moto'        => 'cfmoto',
            'kinetic green'  => 'kinetic',

            // Common emission rating tags
            ' bs6'           => '',
            ' bs4'           => '',
            ' bsvi'          => '',
            ' bsiv'          => '',

            // Trim abbreviations
            ' std'           => ' standard',
            ' standard'      => ' std',
            ' dlx'           => ' deluxe',
            ' deluxe'        => ' dlx',
            ' disc'          => ' disk',
            ' disk'          => ' disc',
            ' abs'           => '',
            ' cbs'           => '',
        ];

        foreach ($substitutions as $from => $to) {
            if (str_contains($base, $from)) {
                $variants[] = str_replace($from, $to, $base);
            }
        }

        return array_unique(array_filter($variants));
    }

    private function normalize(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }

    private function makeOverrides(): array
    {
        return [];
    }
    private function modelOverrides(): array
    {
        return [];
    }
    private function trimOverrides(): array
    {
        return [];
    }

    private function saveChunked(string $label, array $rows, string $model, array $unique, array $update): void
    {
        $this->info("Saving {$label}...");
        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            $model::upsert($chunk, $unique, $update);
        }
        $this->line("  → Saved " . count($rows) . ' mapping rows.');
    }
}
