<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ]);

        $file = $request->file('file');
        if (!$file) {
            return response()->json(['message' => 'Missing file.'], 422);
        }

        $bytes = @file_get_contents($file->getRealPath());
        if ($bytes === false || $bytes === '') {
            return response()->json(['message' => 'Unable to read upload.'], 422);
        }

        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return response()->json(['message' => 'Unsupported image type.'], 422);
        }

        $width = is_array($info) ? (int) ($info[0] ?? 0) : 0;
        $height = is_array($info) ? (int) ($info[1] ?? 0) : 0;
        if ($width <= 0 || $height <= 0) {
            return response()->json(['message' => 'Invalid image dimensions.'], 422);
        }
        if ($width > 12000 || $height > 12000) {
            return response()->json(['message' => 'Image dimensions too large.'], 422);
        }
        if (($width * $height) > 50_000_000) {
            return response()->json(['message' => 'Image megapixels too large.'], 422);
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return response()->json(['message' => 'Invalid image content.'], 422);
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => function_exists('imagewebp') ? 'webp' : 'png',
            default => 'png',
        };

        $outMime = match ($ext) {
            'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $tmpPath = tempnam(sys_get_temp_dir(), 'uplimg_');
        if (!$tmpPath) {
            imagedestroy($image);
            return response()->json(['message' => 'Unable to allocate temp file.'], 500);
        }

        $outPath = $tmpPath . '.' . $ext;

        $encodedOk = false;
        if ($ext === 'jpg') {
            $encodedOk = imagejpeg($image, $outPath, 90);
        } elseif ($ext === 'webp') {
            $encodedOk = imagewebp($image, $outPath, 85);
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $encodedOk = imagepng($image, $outPath, 6);
        }

        imagedestroy($image);
        @unlink($tmpPath);

        if (!$encodedOk || !is_file($outPath)) {
            @unlink($outPath);
            return response()->json(['message' => 'Unable to process image.'], 422);
        }

        $name = Str::random(40) . '.' . $ext;
        $relative = 'uploads/' . $name;

        $stream = fopen($outPath, 'rb');
        if ($stream === false) {
            @unlink($outPath);
            return response()->json(['message' => 'Unable to read processed image.'], 500);
        }

        Storage::disk('public')->put($relative, $stream, [
            'visibility' => 'public',
            'ContentType' => $outMime,
        ]);

        fclose($stream);
        @unlink($outPath);

        $baseUrl = rtrim((string) config('filesystems.disks.public.url', '/storage'), '/');
        $url = $baseUrl . '/' . ltrim($relative, '/');

        return response()->json(['location' => $url]);
    }
}
