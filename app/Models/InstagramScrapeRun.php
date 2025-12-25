<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramScrapeRun extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'has_more_pages' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InstagramProfile::class, 'instagram_profile_id');
    }

    public function hitExisting(): bool
    {
        return $this->posts_skipped > 0;
    }

    public function scopeForProfile($query, int $profileId)
    {
        return $query->where('instagram_profile_id', $profileId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }
}
