<?php

namespace App\Enums;

enum MediaType: int
{
    case Photo = 1;
    case Video = 2;
    case Story = 3;
    case Reels = 4;
    case Highlight = 5;
    case ShoppingPost = 6;
    case LiveVideo = 7;
    case Album = 8;

    public function label(): string
    {
        return match($this) {
            self::Photo => 'Photo',
            self::Video => 'Video',
            self::Story => 'Story',
            self::Reels => 'Reels',
            self::Highlight => 'Highlight',
            self::ShoppingPost => 'Shopping Post',
            self::LiveVideo => 'Live Video',
            self::Album => 'Album',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
