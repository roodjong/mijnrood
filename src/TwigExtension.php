<?php

namespace App;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension {

    public function getFilters() {
        return [
            new TwigFilter('format_price', [self::class, 'formatPrice']),
            new TwigFilter('format_size', [self::class, 'formatSize'])
        ];
    }

    public static function formatPrice(int $cents): string {
        return '€ ' . number_format($cents / 100, 2, ',', '.');
    }

    public static function formatSize(int $bytes): string {
        if ($bytes < 2)
            return $bytes.' byte';

        foreach ([' bytes', ' kB', ' MB', ' GB', ' TB'] as $i => $text) {
            $thisBytes = 1024 ** ($i + 1);
            if ($bytes < $thisBytes)
                return number_format($bytes / $thisBytes * 1024, 0, ',', '.') . $text;
        }
    }

}
