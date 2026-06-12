<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Insurance\Insurer;
use App\Models\Make;
use App\Models\VehicleModel;
use App\Models\Trim;
use App\Models\MakeMapping;
use App\Models\ModelMapping;
use App\Models\TrimMapping;
use Illuminate\Support\Facades\DB;

class VehicleMappingSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/2W.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $this->command->info("Starting optimized bulk vehicle mapping seed...");

        // Resolve or create Tata AIG Insurer
        $insurer = Insurer::firstOrCreate(
            ['company_name' => 'TATA AIG GENERAL INSURANCE'],
            ['status' => 'active', 'regulatory_reg_no' => '108']
        );

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("Could not open CSV file.");
            return;
        }

        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map('trim', $headers);

        $makeNoIdx = array_search('num_manufacturercode', $headers);
        $makeNameIdx = array_search('txt_manufacturername', $headers);
        $modelNoIdx = array_search('num_model_code', $headers);
        $modelNameIdx = array_search('txt_model_name', $headers);
        $variantNoIdx = array_search('num_model_variant_code', $headers);
        $variantNameIdx = array_search('txt_model_variant', $headers);

        if (
            $makeNoIdx === false || $makeNameIdx === false || 
            $modelNoIdx === false || $modelNameIdx === false || 
            $variantNoIdx === false || $variantNameIdx === false
        ) {
            $this->command->error("Required columns not found in CSV headers.");
            fclose($handle);
            return;
        }

        $distinctMakes = [];
        $distinctModels = [];
        $distinctTrims = [];
        $rows = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < count($headers)) {
                continue;
            }

            $makeNo = trim($row[$makeNoIdx]);
            $makeName = strtoupper(trim($row[$makeNameIdx]));
            $modelNo = trim($row[$modelNoIdx]);
            $modelName = strtoupper(trim($row[$modelNameIdx]));
            $variantNo = trim($row[$variantNoIdx]);
            $variantName = strtoupper(trim($row[$variantNameIdx]));

            if (empty($makeName) || empty($modelName) || empty($variantNo)) {
                continue;
            }

            $distinctMakes[$makeName] = true;
            $distinctModels["{$makeName}|||{$modelName}"] = [
                'make_name' => $makeName,
                'model_name' => $modelName
            ];
            $distinctTrims["{$makeName}|||{$modelName}|||{$variantName}"] = [
                'make_name' => $makeName,
                'model_name' => $modelName,
                'trim_name' => $variantName
            ];

            $rows[] = [
                'make_no' => $makeNo,
                'make_name' => $makeName,
                'model_no' => $modelNo,
                'model_name' => $modelName,
                'variant_no' => $variantNo,
                'variant_name' => $variantName
            ];
        }
        fclose($handle);

        DB::beginTransaction();

        try {
            // --- 1. SEED MAKES ---
            $existingMakes = Make::pluck('id', 'make_name')->toArray();
            $existingMakes = array_change_key_case($existingMakes, CASE_UPPER);

            $newMakes = [];
            foreach (array_keys($distinctMakes) as $mName) {
                if (!isset($existingMakes[$mName])) {
                    $newMakes[] = [
                        'make_name' => $mName,
                        'make_org_name' => $mName,
                        'category_id' => 491,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            if (!empty($newMakes)) {
                foreach (array_chunk($newMakes, 500) as $chunk) {
                    Make::insert($chunk);
                }
            }

            // Re-fetch all makes
            $makeMap = Make::pluck('id', 'make_name')->toArray();
            $makeMap = array_change_key_case($makeMap, CASE_UPPER);

            // --- 2. SEED MODELS ---
            $existingModels = [];
            VehicleModel::all(['id', 'make_id', 'model_name'])->each(function($m) use (&$existingModels) {
                $existingModels["{$m->make_id}-" . strtoupper($m->model_name)] = $m->id;
            });

            $newModels = [];
            foreach ($distinctModels as $m) {
                $makeId = $makeMap[$m['make_name']] ?? null;
                if ($makeId) {
                    $modelKey = "{$makeId}-{$m['model_name']}";
                    if (!isset($existingModels[$modelKey])) {
                        $newModels[] = [
                            'make_id' => $makeId,
                            'model_name' => $m['model_name'],
                            'model_org_name' => $m['model_name'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            if (!empty($newModels)) {
                foreach (array_chunk($newModels, 500) as $chunk) {
                    VehicleModel::insert($chunk);
                }
            }

            // Re-fetch all models
            $modelMap = [];
            VehicleModel::all(['id', 'make_id', 'model_name'])->each(function($m) use (&$modelMap) {
                $modelMap["{$m->make_id}-" . strtoupper($m->model_name)] = $m->id;
            });

            // --- 3. SEED TRIMS ---
            $existingTrims = [];
            Trim::all(['id', 'make_id', 'model_id', 'trim_name'])->each(function($t) use (&$existingTrims) {
                $existingTrims["{$t->make_id}-{$t->model_id}-" . strtoupper($t->trim_name)] = $t->id;
            });

            $newTrims = [];
            foreach ($distinctTrims as $t) {
                $makeId = $makeMap[$t['make_name']] ?? null;
                $modelId = $modelMap["{$makeId}-{$t['model_name']}"] ?? null;
                if ($makeId && $modelId) {
                    $trimKey = "{$makeId}-{$modelId}-{$t['trim_name']}";
                    if (!isset($existingTrims[$trimKey])) {
                        $newTrims[] = [
                            'make_id' => $makeId,
                            'model_id' => $modelId,
                            'trim_name' => $t['trim_name'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            if (!empty($newTrims)) {
                foreach (array_chunk($newTrims, 500) as $chunk) {
                    Trim::insert($chunk);
                }
            }

            // Re-fetch all trims
            $trimMap = [];
            Trim::all(['id', 'make_id', 'model_id', 'trim_name'])->each(function($t) use (&$trimMap) {
                $trimMap["{$t->make_id}-{$t->model_id}-" . strtoupper($t->trim_name)] = $t->id;
            });

            // --- 4. SEED MAPPINGS (UPSERT IS SAFE) ---
            $makeMappings = [];
            $modelMappings = [];
            $trimMappings = [];

            foreach ($rows as $r) {
                $makeId = $makeMap[$r['make_name']] ?? null;
                $modelId = $modelMap["{$makeId}-{$r['model_name']}"] ?? null;
                $trimId = $trimMap["{$makeId}-{$modelId}-{$r['variant_name']}"] ?? null;

                if ($makeId) {
                    $makeMappings["{$insurer->id}-{$makeId}"] = [
                        'insurer_id' => $insurer->id,
                        'dr_make_id' => $makeId,
                        'insurer_make_id' => $r['make_no'],
                        'insurer_make_name' => $r['make_name'],
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if ($modelId) {
                    $modelMappings["{$insurer->id}-{$modelId}"] = [
                        'insurer_id' => $insurer->id,
                        'category_id' => 491, // Two Wheeler
                        'dr_make_id' => $makeId,
                        'dr_model_id' => $modelId,
                        'insurer_model_id' => $r['model_no'],
                        'insurer_model_name' => $r['model_name'],
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if ($trimId) {
                    $trimMappings["{$insurer->id}-{$trimId}"] = [
                        'insurer_id' => $insurer->id,
                        'dr_make_id' => $makeId,
                        'dr_model_id' => $modelId,
                        'dr_trim_id' => $trimId,
                        'insurer_trim_id' => $r['variant_no'],
                        'insurer_trim_name' => $r['variant_name'],
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // Save mappings using upsert
            foreach (array_chunk(array_values($makeMappings), 500) as $chunk) {
                MakeMapping::upsert($chunk, ['insurer_id', 'dr_make_id'], ['insurer_make_id', 'insurer_make_name', 'status']);
            }
            foreach (array_chunk(array_values($modelMappings), 500) as $chunk) {
                ModelMapping::upsert($chunk, ['insurer_id', 'dr_model_id'], ['category_id', 'dr_make_id', 'insurer_model_id', 'insurer_model_name', 'status']);
            }
            foreach (array_chunk(array_values($trimMappings), 500) as $chunk) {
                TrimMapping::upsert($chunk, ['insurer_id', 'dr_trim_id'], ['dr_make_id', 'dr_model_id', 'insurer_trim_id', 'insurer_trim_name', 'status']);
            }

            DB::commit();
            $this->command->info("Successfully seeded " . count($makeMap) . " Makes, " . count($modelMap) . " Models, and " . count($trimMap) . " Trims/Mappings in bulk!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to seed: " . $e->getMessage());
        }
    }
}
