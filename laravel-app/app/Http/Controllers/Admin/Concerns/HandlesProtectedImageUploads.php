<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores uploaded images (menu item photos, ingredient photos) on the
 * private 'local' disk, storage/app/, which sits outside the web server's
 * document root and is never directly reachable by any URL, regardless of
 * server configuration, rather than the 'public' disk, which Laravel
 * symlinks straight into the public webroot (public/storage). Images are
 * served back through a dedicated route (see ProtectedFileController)
 * instead of static file serving, so even if a malicious file somehow
 * passed the mimes/size validation the caller applies before this runs, the
 * web server itself never has direct access to execute it, the file simply
 * isn't anywhere the web server's document root can reach.
 *
 * Shared by MenuController and InventoryController, mirroring how both
 * already share ClampsPaginationPage: identical logic, one place to fix it.
 */
trait HandlesProtectedImageUploads{
    // Stores the file under storage/app/{type}/{randomHash}.{ext}, the
    // random filename comes from UploadedFile::store()'s own hashName()
    // default, never the client-provided original filename, and returns
    // the URL the admin's browser should load it from: a Laravel route, not
    // a static file path. $type must be one of ProtectedFileController's
    // ALLOWED_TYPES.
    protected function storeProtectedImage(UploadedFile $file, string $type): string{
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        $path = $file->store($type, $disk);

        return route('files.show', [
            'type' => $type,
            'filename' => basename($path),
        ]);
    }

    // Deletes the underlying file for a previously stored image_url, given
    // the type it was stored under. Safe to call with a null/empty URL (a
    // record that never had an image), does nothing in that case.
    protected function deleteProtectedImage(?string $imageUrl, string $type): void{
        if (! $imageUrl) {
            return;
        }

        $filename = basename(parse_url($imageUrl, PHP_URL_PATH) ?? '');

        if ($filename === '') {
            return;
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        Storage::disk($disk)->delete("{$type}/{$filename}");
    }
}
