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
        'scheduled_times' => 'array',
    ];

    public const DEFAULT_SCHEDULED_TIMES = ['13:00', '17:00', '20:00'];

    public function getScheduledTimesAttribute($value)
    {
        if (is_null($value)) {
            return self::DEFAULT_SCHEDULED_TIMES;
        }
        
        // If it's already an array (due to casting), use it
        if (is_array($value)) {
            return empty($value) ? self::DEFAULT_SCHEDULED_TIMES : $value;
        }

        $decoded = json_decode($value, true);
        return empty($decoded) ? self::DEFAULT_SCHEDULED_TIMES : $decoded;
    }

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

    public function getNextScheduledAt(): \Illuminate\Support\Carbon
    {
        $times = $this->scheduled_times;
        sort($times);
        
        $tz = $this->timezone ?? config('app.timezone', 'UTC');
        $nowInTz = now($tz);
        
        foreach ($times as $time) {
            [$hour, $minute] = explode(':', $time);
            $scheduled = now($tz)->setHour((int)$hour)->setMinute((int)$minute)->setSecond(0);
            
            if ($scheduled->isAfter($nowInTz)) {
                // Convert back to application timezone (UTC) for storage
                return $scheduled->setTimezone(config('app.timezone', 'UTC'));
            }
        }
        
        // If no more today, get first one tomorrow
        [$hour, $minute] = explode(':', $times[0] ?? '13:00');
        return now($tz)->addDay()->setHour((int)$hour)->setMinute((int)$minute)->setSecond(0)
            ->setTimezone(config('app.timezone', 'UTC'));
    }
}
