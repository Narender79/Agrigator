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
        $vehicleType = str_contains(basename($csvPath), '2W') ? '2w' : '4w';

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
            // --- 1. SEED MAPPINGS (UPSERT IS SAFE) ---
            $makeMappings = [];
            $modelMappings = [];
            $trimMappings = [];

            foreach ($rows as $r) {
                if (!empty($r['make_no'])) {
                    $makeMappings["{$insurer->id}-{$vehicleType}-{$r['make_no']}"] = [
                        'insurer_id' => $insurer->id,
                        'insurer_make_id' => $r['make_no'],
                        'insurer_make_name' => $r['make_name'],
                        'vehicle_type' => $vehicleType,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if (!empty($r['model_no'])) {
                    $modelMappings["{$insurer->id}-{$vehicleType}-{$r['model_no']}"] = [
                        'insurer_id' => $insurer->id,
                        'category_id' => 491, // Two Wheeler
                        'insurer_make_id' => $r['make_no'],
                        'insurer_model_id' => $r['model_no'],
                        'insurer_model_name' => $r['model_name'],
                        'vehicle_type' => $vehicleType,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if (!empty($r['variant_no'])) {
                    $trimMappings["{$insurer->id}-{$vehicleType}-{$r['variant_no']}"] = [
                        'insurer_id' => $insurer->id,
                        'insurer_make_id' => $r['make_no'],
                        'insurer_model_id' => $r['model_no'],
                        'insurer_trim_id' => $r['variant_no'],
                        'insurer_trim_name' => $r['variant_name'],
                        'vehicle_type' => $vehicleType,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // Save mappings using upsert
            foreach (array_chunk(array_values($makeMappings), 500) as $chunk) {
                MakeMapping::upsert($chunk, ['insurer_id', 'vehicle_type', 'insurer_make_id'], ['insurer_make_name', 'status']);
            }
            foreach (array_chunk(array_values($modelMappings), 500) as $chunk) {
                ModelMapping::upsert($chunk, ['insurer_id', 'vehicle_type', 'insurer_model_id'], ['category_id', 'insurer_make_id', 'insurer_model_name', 'status']);
            }
            foreach (array_chunk(array_values($trimMappings), 500) as $chunk) {
                TrimMapping::upsert($chunk, ['insurer_id', 'vehicle_type', 'insurer_trim_id'], ['insurer_make_id', 'insurer_model_id', 'insurer_trim_name', 'status']);
            }

            DB::commit();
            $this->command->info("Successfully seeded " . count($makeMappings) . " Makes, " . count($modelMappings) . " Models, and " . count($trimMappings) . " Trims/Mappings in bulk!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Failed to seed: " . $e->getMessage());
        }
    }
}
