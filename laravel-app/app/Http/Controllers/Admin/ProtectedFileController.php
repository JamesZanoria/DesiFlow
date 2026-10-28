<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded images (menu item photos, ingredient photos) that live on
 * the private 'local' disk, storage/app/, outside the public webroot, so
 * the web server never has direct access to these files at all. Every
 * request here goes through Laravel, which decides the Content-Type from a
 * fixed allowlist rather than trusting whatever's actually stored, and the
 * filename is validated strictly, both here and via the route's own regex
 * constraint in web.php, against path traversal before it ever touches the
 * filesystem.
 *
 * Gated behind the same 'auth' + 'role:admin' middleware as the rest of the
 * admin panel (see web.php), these images only ever appear inside pages
 * that already require login, so there's no reason for this route to be
 * reachable by anyone who couldn't already see the image some other way.
 */
class ProtectedFileController extends Controller{
    // Keep in sync with the subdirectories MenuController/InventoryController
    // actually store into ('menu-items', 'ingredients'), anything not in
    // this list is refused before touching the filesystem at all.
    private const ALLOWED_TYPES = ['menu-items', 'ingredients', 'avatars'];

    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function show(Request $request, string $type, string $filename): StreamedResponse{
        abort_unless(in_array($type, self::ALLOWED_TYPES, true), 404);

        // Belt-and-suspenders on top of the route's own regex constraint
        // (see web.php): reject anything that isn't exactly
        // "<safe-characters>.<jpg|jpeg|png>", no path separators, no "..",
        // no null bytes, before it's ever used to build a filesystem path.
        abort_unless((bool) preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png)$/', $filename), 404);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $path = "{$type}/{$filename}";

        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        // The Content-Type served back is decided from the fixed map above,
        // driven only by the filename's own extension, never from
        // whatever MIME type the stored file happens to sniff as. This is
        // what stops a content-type-confusion attempt from ever being
        // served back as anything other than a plain image, even if a
        // bypassed validation somehow let something else get this far.
        return Storage::disk($disk)->response($path, null, [
            'Content-Type' => self::CONTENT_TYPES[$extension],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}