<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstagramProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'bio_links' => 'array',
        'pronouns' => 'array',
        'is_private' => 'boolean',
        'is_verified' => 'boolean',
        'is_business' => 'boolean',
        'initial_scrape_done' => 'boolean',
        'scrape_interval_hours' => 'integer',
        'posts_per_request' => 'integer',
        'local_post_count' => 'integer',
        'last_scraped_at' => 'datetime',
        'next_scrape_at' => 'datetime',
        'initial_scrape_at' => 'datetime',
    ];

    public function posts()
    {
        return $this->hasMany(InstagramPost::class, 'ig_id', 'ig_id');
    }

    public function scrapeRuns(): HasMany
    {
        return $this->hasMany(InstagramScrapeRun::class);
    }

    public function processingRuns(): HasMany
    {
        return $this->hasMany(InstagramProcessingRun::class);
    }

    public function latestScrapeRun(): HasOne
    {
        return $this->hasOne(InstagramScrapeRun::class)->latestOfMany();
    }

    public function latestProcessingRun(): HasOne
    {
        return $this->hasOne(InstagramProcessingRun::class)->latestOfMany();
    }

    public function updateLocalPostCount(): void
    {
        $this->update(['local_post_count' => $this->posts()->count()]);
    }

    public function needsInitialScrape(): bool
    {
        return !$this->initial_scrape_done;
    }

    public function getCoveragePercentageAttribute(): float
    {
        if (!$this->media_count || $this->media_count === 0) {
            return 0;
        }
        return round(($this->local_post_count / $this->media_count) * 100, 2);
    }
}
