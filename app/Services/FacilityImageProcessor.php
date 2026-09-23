<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FacilityImageProcessor
{
    private const MAX_DIMENSION = 1200;

    private const WEBP_QUALITY = 80;

    public function store(UploadedFile $image): string
    {
        $contents = file_get_contents($image->getRealPath());
        $source = $contents === false ? false : imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('The uploaded facility image could not be processed.');
        }

        try {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION / max($sourceWidth, $sourceHeight));
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));

            $processed = imagecreatetruecolor($width, $height);

            if ($processed === false) {
                throw new RuntimeException('The facility image canvas could not be created.');
            }

            try {
                imagealphablending($processed, false);
                imagesavealpha($processed, true);
                $transparent = imagecolorallocatealpha($processed, 0, 0, 0, 127);
                imagefill($processed, 0, 0, $transparent);

                if (! imagecopyresampled(
                    $processed,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $width,
                    $height,
                    $sourceWidth,
                    $sourceHeight,
                )) {
                    throw new RuntimeException('The facility image could not be resized.');
                }

                ob_start();
                $encoded = imagewebp($processed, null, self::WEBP_QUALITY);
                $webp = ob_get_clean();

                if (! $encoded || ! is_string($webp) || $webp === '') {
                    throw new RuntimeException('The facility image could not be compressed.');
                }

                $path = 'facilities/'.Str::uuid().'.webp';

                if (! Storage::disk('public')->put($path, $webp)) {
                    throw new RuntimeException('The compressed facility image could not be stored.');
                }

                return $path;
            } finally {
                imagedestroy($processed);
            }
        } finally {
            imagedestroy($source);
        }
    }
}
