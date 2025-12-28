<?php

namespace App\Console\Commands;

use App\Jobs\FullPipelineJob;
use App\Models\InstagramProfile;
use App\Models\InstagramScrapeRun;
use Illuminate\Console\Command;

class RunDueScrapes extends Command
{
    protected $signature = 'scrape:run-due {--dry-run : Show which profiles would be scraped without actually running}';
    protected $description = 'Run full pipeline for profiles due for scraping based on their scrape interval';

    public function handle(): int
    {
        $now = now();

        $profiles = InstagramProfile::where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('next_scrape_at')
                    ->orWhere('next_scrape_at', '<=', $now);
            })
            ->get();

        if ($profiles->isEmpty()) {
            $this->info('No profiles are due for scraping.');
            return Command::SUCCESS;
        }

        $this->info("Found {$profiles->count()} profile(s) due for scraping:");

        foreach ($profiles as $profile) {
            $this->line("  - {$profile->username} (last scraped: " . ($profile->last_scraped_at?->diffForHumans() ?? 'never') . ")");

            if ($this->option('dry-run')) {
                continue;
            }

            // Create scrape run to track the pipeline
            $run = InstagramScrapeRun::create([
                'instagram_profile_id' => $profile->id,
                'type' => 'full_pipeline',
                'status' => 'running',
                'started_at' => now(),
            ]);

            // Dispatch the full pipeline job
            FullPipelineJob::dispatch($profile->id, $run->id);

            // Update next_scrape_at for this profile based on its schedule
            $profile->update([
                'last_scraped_at' => now(),
                'next_scrape_at' => $profile->getNextScheduledAt(),
            ]);

            $this->info("  ✓ Dispatched pipeline for {$profile->username}");
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run - no jobs were dispatched.');
        } else {
            $this->info("Dispatched {$profiles->count()} pipeline job(s).");
        }

        return Command::SUCCESS;
    }
}
