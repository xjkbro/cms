<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Generate a resized image URL - simple equivalent to asset(Storage::url($path))
     * 
     * Usage:
     * ImageHelper::url('1/image.jpg') // Original size
     * ImageHelper::url('1/image.jpg', 300) // Width 300px
     * ImageHelper::url('1/image.jpg', 300, 200) // 300x200px
     */
    public static function url(string $path, ?int $width = null, ?int $height = null): string
    {
        $url = url('/images/' . $path);
        $params = [];

        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;

        return $url . (empty($params) ? '' : '?' . http_build_query($params));
    }

    /**
     * Generate a thumbnail URL (common sizes)
     */
    public static function thumbnail(string $path, int $size = 150): string
    {
        return self::url($path, $size, $size);
    }

    /**
     * Generate a medium sized image URL
     */
    public static function medium(string $path, int $width = 800): string
    {
        return self::url($path, $width);
    }

    /**
     * Generate a large sized image URL
     */
    public static function large(string $path, int $width = 1200): string
    {
        return self::url($path, $width);
    }
}
