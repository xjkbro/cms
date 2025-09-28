<?php

if (!function_exists('image_url')) {
    /**
     * Generate a resized image URL - equivalent to asset(Storage::url($path))
     * 
     * Usage:
     * image_url('1/image.jpg') // Original size
     * image_url('1/image.jpg', 300) // Width 300px
     * image_url('1/image.jpg', 300, 200) // 300x200px
     */
    function image_url(string $path, ?int $width = null, ?int $height = null): string
    {
        $url = url('/images/' . $path);
        $params = [];

        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;

        return $url . (empty($params) ? '' : '?' . http_build_query($params));
    }
}
