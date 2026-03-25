<?php

namespace App\Services\Extraction;

class ImageOptimizer
{
    /**
     * Downsample image if it's too large to save tokens and speed up API.
     */
    public function optimize(string $path): string
    {
        if (!extension_loaded('gd')) {
            return $path;
        }

        $info = getimagesize($path);
        if (!$info) return $path;

        [$width, $height, $type] = $info;
        $maxSize = 1600;

        if ($width <= $maxSize && $height <= $maxSize) {
            return $path;
        }

        // Calculate new dimensions
        $ratio = $width / $height;
        if ($ratio > 1) {
            $newWidth = $maxSize;
            $newHeight = (int)($maxSize / $ratio);
        } else {
            $newHeight = $maxSize;
            $newWidth = (int)($maxSize * $ratio);
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default       => null,
        };

        if (!$src) return $path;

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        // Handle transparency for PNG/WEBP
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $tempPath = storage_path('app/temp_ai_image_' . uniqid() . '.jpg');
        imagejpeg($dst, $tempPath, 80); // 80 quality is plenty for OCR

        imagedestroy($src);
        imagedestroy($dst);

        return $tempPath;
    }
}
