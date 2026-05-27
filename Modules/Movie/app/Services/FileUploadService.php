<?php

namespace Modules\Movie\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Movie\Contracts\FileUploadServiceInterface;
use Throwable;

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
        try {
            $relativePath = $this->extractRelativePath($path);
            if ($relativePath === null || !Storage::disk($this->disk)->exists($relativePath)) {
                return false;
            }
            return Storage::disk($this->disk)->delete($relativePath);
        } catch (Throwable) {
            return false;
        }
    }
    private function extractRelativePath(string $value): ?string
    {
        $diskUrl = rtrim(Storage::disk($this->disk)->url(''), '/');
        if ($diskUrl !== '' && str_starts_with($value, $diskUrl)) {
            return ltrim(substr($value, strlen($diskUrl)), '/');
        }
        if (!str_contains($value, '://')) {
            return ltrim($value, '/');
        }
        return null;
    }
}
