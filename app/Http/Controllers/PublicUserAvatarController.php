<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicUserAvatarController extends Controller
{
    /**
     * Serve user avatar files from storage/app/public/users.
     * Used so profile images work when public/storage is not symlinked to storage/app/public.
     */
    public function show(string $filename): BinaryFileResponse
    {
        $filename = basename(urldecode($filename));
        if ($filename === '' || $filename === '.' || $filename === '..' || strlen($filename) > 255) {
            abort(404);
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (! in_array($ext, $allowedExt, true)) {
            abort(404);
        }
        $stem = (string) pathinfo($filename, PATHINFO_FILENAME);
        if ($stem === '' || strlen($stem) > 200) {
            abort(404);
        }
        // Safe stem: letters, numbers, common punctuation; legacy uploads may include spaces.
        if (! preg_match('/^[\pL\pN._\-\s()]+$/u', $stem)) {
            abort(404);
        }

        $path = storage_path('app/public/users/'.$filename);
        if (! is_readable($path) || ! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
