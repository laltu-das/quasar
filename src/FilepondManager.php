<?php

namespace Laltu\Quasar;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Models\Filepond;

class FilepondManager
{
    private $fieldValue;
    private $tempDisk;
    private $isMultipleUpload;
    private $fieldModel;
    private $isSoftDeletable;

    public function __construct()
    {
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->isSoftDeletable = config('filepond.soft_delete', true);
    }

    public function field($field): self
    {
        $this->fieldValue = $field ? array_map([$this, 'decrypt'], (array) $field) : null;
        $this->isMultipleUpload = is_array($field);
        $this->fieldModel = $this->loadFieldModel();
        return $this;
    }

    private function decrypt($data)
    {
        return Crypt::decrypt($data, true);
    }

    private function loadFieldModel()
    {
        return $this->fieldValue ? Filepond::whereIn('id', collect($this->fieldValue)->pluck('id'))->get() : null;
    }

    public function getFile()
    {
        return $this->fieldModel ? $this->fieldModel->map(fn($filepond) => new UploadedFile(
            Storage::disk($this->tempDisk)->path($filepond->filepath),
            $filepond->filename,
            $filepond->mimetypes,
            UPLOAD_ERR_OK,
            true
        ))->toArray() : null;
    }

    public function getDataURL()
    {
        return $this->fieldModel ? $this->fieldModel->map(fn($filepond) => 'data:' . $filepond->mimetypes . ';base64,' . base64_encode(Storage::disk($this->tempDisk)->get($filepond->filepath)))->toArray() : null;
    }

    public function copyTo(string $path, string $disk = '', string $visibility = '')
    {
        if (!$this->fieldModel) return null;

        return $this->fieldModel->map(function ($filepond) use ($path, $disk, $visibility) {
            $to = $path . '-' . $filepond->id;
            $storagePath = $to . '.' . $filepond->extension;
            $permanentDisk = $disk ?: $filepond->disk;
            Storage::disk($permanentDisk)->put($storagePath, Storage::disk($this->tempDisk)->get($filepond->filepath), $visibility);
            return ['url' => Storage::disk($permanentDisk)->url($storagePath)];
        })->toArray();
    }

    public function moveTo(string $path, string $disk = '', string $visibility = ''): ?array
    {
        if (!$this->fieldModel) return null;

        $responses = [];
        foreach ($this->fieldModel as $filepond) {
            $newPath = $path . '-' . $filepond->id;
            $fullPath = $newPath . '.' . ($filepond->extension ?: pathinfo($filepond->filename, PATHINFO_EXTENSION));
            $permanentDisk = $disk ?: $filepond->disk;

            Storage::disk($permanentDisk)->put($fullPath, Storage::disk($this->tempDisk)->get($filepond->filepath), $visibility);
            $responses[] = $this->generateResponse($filepond, $fullPath, $permanentDisk);
            $this->cleanUp($filepond);
        }

        return $this->isMultipleUpload ? $responses : $responses[0] ?? null;
    }

    private function generateResponse(Filepond $filepond, string $fullPath, string $disk): array
    {
        return [
            'id' => $filepond->id,
            'dirname' => dirname($fullPath),
            'basename' => basename($fullPath),
            'extension' => $filepond->extension ?: pathinfo($filepond->filename, PATHINFO_EXTENSION),
            'filename' => basename($fullPath, '.' . $filepond->extension),
            'location' => $fullPath,
            'url' => Storage::disk($disk)->url($fullPath),
        ];
    }

    private function cleanUp(Filepond $filepond): void
    {
        if (!$this->isSoftDeletable) {
            Storage::disk($this->tempDisk)->delete($filepond->filepath);
            $filepond->forceDelete();
        } else {
            $filepond->delete();
        }
    }


    public function delete(): void
    {
        if (!$this->fieldModel) return;
        foreach ($this->fieldModel as $filepond) {
            $method = $this->isSoftDeletable ? 'delete' : 'forceDelete';
            $filepond->$method();
            if (!$this->isSoftDeletable) {
                Storage::disk($this->tempDisk)->delete($filepond->filepath);
            }
        }
    }
}
