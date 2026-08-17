<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AdoptionDocumentStorageService
{
    public function exists(?string $path): bool
    {
        return $this->locate($path) !== null;
    }

    public function download(string $path, string $downloadName): Response
    {
        $location = $this->locate($path);

        if (!$location) {
            throw new RuntimeException('The requested adoption document could not be located.');
        }

        if ($location['type'] === 'disk') {
            return Storage::disk($location['disk'])->download($path, $downloadName);
        }

        return response()->download($location['path'], $downloadName);
    }

    private function locate(?string $path): ?array
    {
        if (!$path || str_contains(str_replace('\\', '/', $path), '../')) {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return ['type' => 'disk', 'disk' => $disk];
            }
        }

        $storageRoot = realpath(storage_path('app'));
        $legacyPath = realpath(storage_path('app/' . ltrim($path, '/\\')));

        if (
            $storageRoot
            && $legacyPath
            && is_file($legacyPath)
            && str_starts_with(
                $legacyPath,
                $storageRoot . DIRECTORY_SEPARATOR
            )
        ) {
            return ['type' => 'legacy', 'path' => $legacyPath];
        }

        return null;
    }
}
