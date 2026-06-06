<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Make;
use App\Models\VehicleModel;

class FillMakeCategoryIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fill-make-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =  'Fill category_id in the make table by resolving it from associated models.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Step 1: Populating categories table (81 for 4w, 491 for 2w)...");
        // 1. Insert/Update 4 wheelers category (ID: 81)
        \Illuminate\Support\Facades\DB::table('category')->updateOrInsert(
            ['id' => 81],
            [
                'category_name' => '4 wheelers',
                'type'          => '4w',
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now()
            ]
        );
        // 2. Insert/Update 2 wheelers category (ID: 491)
        \Illuminate\Support\Facades\DB::table('category')->updateOrInsert(
            ['id' => 491],
            [
                'category_name' => '2 wheelers',
                'type'          => '2w',
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now()
            ]
        );
        $this->info("Categories table populated successfully!");

        $this->info("Starting to synch category_id in mkaes table from model table----");

        $makes = Make::all();
        $total = $makes->count();
        $updated = 0;
        $skipped = 0;

        $this->info("Total in data found in db : {$total}");

        foreach ($makes as $make) {
            $model = VehicleModel::where('make_id', $make->id)->whereNotNull('category_id')->first();

            if ($model) {
                $categoryId = $model->category_id;
                $make->category_id = $categoryId;

                $make->save();

                $this->line("Updated make: {$make->make_name} (ID: {$make->id}) ➔ category_id: {$categoryId}");
                $updated++;
            } else {
                $this->warn("Skipped make: {$make->make_name} (ID: {$make->id}) - No associated models found.");
                $skipped++;
            }
        }
        $this->info('--- Synchronization Completed ---');
        $this->info("Total Updated: {$updated} | Total Skipped: {$skipped}");
        return Command::SUCCESS;
    }
}
