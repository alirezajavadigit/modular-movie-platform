<?php

namespace Modules\Movie\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Movie\Contracts\FileUploadServiceInterface;

class FileUploadService implements FileUploadServiceInterface
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('movie.upload.disk', 'public');
    }

    public function upload(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, $this->disk);

        return Storage::disk($this->disk)->url($path);
    }

    public function delete(string $path): bool
    {
        $relativePath = $this->extractRelativePath($path);

        if (!$relativePath || !Storage::disk($this->disk)->exists($relativePath)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($relativePath);
    }

    private function extractRelativePath(string $url): ?string
    {
        $diskUrl = rtrim(Storage::disk($this->disk)->url(''), '/');

        if (str_starts_with($url, $diskUrl)) {
            return ltrim(str_replace($diskUrl, '', $url), '/');
        }

        return null;
    }
}
