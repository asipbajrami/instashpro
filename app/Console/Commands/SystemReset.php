<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset {--no-scout : Skip Scout indexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a fresh database migration, seed, and reset search indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting System Reset...');

        // 1. Fresh Migration and Seeding
        $this->info('--- Running migrate:fresh --seed ---');
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ], $this->getOutput());

        if (!$this->option('no-scout')) {
            $this->info('--- Resetting Search Indexes ---');
            
            // Delete all indexes
            try {
                Artisan::call('scout:delete-all-indexes', [], $this->getOutput());
            } catch (\Exception $e) {
                $this->warn('Could not delete all indexes (driver might not support it): ' . $e->getMessage());
            }

            // List of searchable models
            $models = [
                'App\Models\Category',
                'App\Models\Product',
                'App\Models\InstagramPost',
                'App\Models\InstagramMedia',
                'App\Models\StructureOutputGroup',
                'App\Models\ProductAttributeValue',
            ];

            foreach ($models as $model) {
                $this->info("Importing $model...");
                try {
                    Artisan::call('scout:import', [
                        'model' => $model,
                    ], $this->getOutput());
                } catch (\Exception $e) {
                    $this->error("Failed to import $model: " . $e->getMessage());
                }
            }
        }

        $this->info('System Reset Complete!');
        return self::SUCCESS;
    }
}
