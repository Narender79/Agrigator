<?php

namespace App\Utils;

class DHelper
{
    /**
     * Generate dynamic CDN URL for relative asset paths.
     *
     * @param string $url
     * @return string
     */
    public static function getCdnUrl(string $url = ""): string
    {
        if (empty($url)) {
            return '';
        }

        if (\Illuminate\Support\Str::contains($url, ['http://', 'https://'])) {
            return $url;
        }

        if (app()->environment('local', 'testing', 'development')) {
            return asset(ltrim($url, '/'));
        }

        $baseUrl = rtrim(env('CDN_URL', env('IMG_URL', 'https://cdn1.acedms.com')), '/');
        return $baseUrl . '/' . ltrim($url, '/');
    }
}
