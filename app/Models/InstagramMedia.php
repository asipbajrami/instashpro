<?php

namespace App\Models;

use App\Services\EmbeddingService;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class InstagramMedia extends Model
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'string',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id', 'post_id');
    }

    public function searchableAs(): string
    {
        return 'instagram_media';
    }

    public function toSearchableArray(): array
    {
        $image = null;

        try {
            if ($this->type === 'video') {
                $image = Storage::get('public/posts/' . $this->shortcode . '/high.jpg');
            } else {
                $image = Storage::get('public/' . $this->media_path);
            }
            $image = $image ? base64_encode($image) : null;
        } catch (Exception $e) {
            Log::error("Error reading media for search: " . $e->getMessage());
        }

        // Pre-compute image embedding via SigLIP2
        $imageEmbedding = null;
        if ($image && config('services.embedding.enabled', false)) {
            try {
                $embeddingService = app(EmbeddingService::class);
                $imageEmbedding = $embeddingService->getImageEmbedding($image);
            } catch (Exception $e) {
                Log::error("Error getting image embedding: " . $e->getMessage());
            }
        }

        return [
            'id' => (string) $this->id,
            'instagram_post_id' => (string) $this->instagram_post_id,
            'type' => $this->type,
            'media_id' => $this->media_id ?? '',
            'shortcode' => $this->shortcode,
            'used_for' => $this->used_for ?? '',
            'updated_at' => now()->timestamp,
            'embedding_clip' => $imageEmbedding,
        ];
    }
}
