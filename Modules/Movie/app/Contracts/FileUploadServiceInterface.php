<?php

namespace Modules\Movie\Contracts;

use Illuminate\Http\UploadedFile;

interface FileUploadServiceInterface
{
    public function upload(UploadedFile $file, string $directory): string;

    public function delete(string $path): bool;
}
