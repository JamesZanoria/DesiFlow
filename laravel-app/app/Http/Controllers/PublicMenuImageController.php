<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMenuImageController extends Controller
{
    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function show(string $filename): StreamedResponse
    {
        abort_unless((bool) preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png)$/', $filename), 404);

        $path = "menu-items/{$filename}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => self::CONTENT_TYPES[strtolower(pathinfo($filename, PATHINFO_EXTENSION))],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
