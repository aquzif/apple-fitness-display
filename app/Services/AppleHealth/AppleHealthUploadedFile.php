<?php

namespace App\Services\AppleHealth;

use Illuminate\Support\Facades\Storage;

class AppleHealthUploadedFile
{
    public function __construct(
        private string $path,
        private ?string $originalFilename = null,
        private ?string $clientExtension = null,
        private ?string $mimeType = null,
        private string $disk = 'local'
    ) {
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function originalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function clientExtension(): ?string
    {
        return $this->clientExtension;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function delete(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
