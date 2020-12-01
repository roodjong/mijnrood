<?php

namespace App;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension {

    public function getFilters() {
        return [
            new TwigFilter('formatPrice', [self::class, 'formatPrice'])
        ];
    }

    public static function formatPrice(int $cents): string {
        return '€ ' . number_format($cents / 100, 2, ',', '.');
    }

}
