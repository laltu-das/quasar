<?php

namespace Laltu\Quasar;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Models\Filepond;

class FilepondManager
{
    private string $fileId;
    private bool $isMultipleUpload;

    public function field($fileId): self
    {
        $this->fileId = $fileId;
        $this->isMultipleUpload = is_array($fileId);
        return $this;
    }

    public function moveTo(string $path): ?array
    {
        $filepond = Filepond::find(Crypt::decryptString($this->fileId));

        if (!$filepond) return null;

        $newPath = $path . '-' . $filepond->id;
        $fullPath = $newPath . '.' . ($filepond->extension ?: pathinfo($filepond->filename, PATHINFO_EXTENSION));
        Storage::disk('s3')->move($filepond->filepath, $fullPath);

        $response = $this->generateResponse($filepond, $fullPath);

        return $this->isMultipleUpload ? [$response] : $response;
    }

    private function generateResponse(Filepond $filepond, string $fullPath): array
    {
        return [
            'id' => Crypt::encryptString($filepond->id),
            'dirname' => dirname($fullPath),
            'basename' => basename($fullPath),
            'extension' => $filepond->extension ?: pathinfo($filepond->filename, PATHINFO_EXTENSION),
            'mimetypes' => $filepond->mimetypes,
            'filename' => basename($fullPath, '.' . $filepond->extension),
            'filesize' => $filepond->filesize,
            'location' => $fullPath,
            'url' => Storage::disk('s3')->url($fullPath),
        ];
    }

    public function delete(): void
    {
        $filepond = Filepond::find($this->fileId);
        if (!$filepond) return;

        Storage::disk('s3')->delete($filepond->filepath);
        $filepond->delete();
    }
}
