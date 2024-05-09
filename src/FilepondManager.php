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
    private $isOwnershipAware;
    private $isSoftDeletable;

    public function setTempDisk(string $tempDisk): self
    {
        $this->tempDisk = $tempDisk;
        return $this;
    }

    protected function setFieldValue($fieldValue): self
    {
        $this->fieldValue = $fieldValue ? array_map([$this, 'decrypt'], (array) $fieldValue) : null;
        $this->isMultipleUpload = is_array($fieldValue);
        return $this;
    }

    protected function decrypt(string $data)
    {
        return Crypt::decrypt($data, true);
    }

    protected function getFieldModel()
    {
        return $this->fieldValue ? Filepond::when($this->isOwnershipAware, fn($query) => $query->owned())
            ->whereIn('id', collect($this->fieldValue)->pluck('id'))->get() : null;
    }

    public function field($field, bool $checkOwnership = true): self
    {
        $this->setFieldValue($field)
            ->setTempDisk(config('filepond.temp_disk', 'local'))
            ->setIsSoftDeletable(config('filepond.soft_delete', true))
            ->setIsOwnershipAware($checkOwnership);
        return $this;
    }

    public function getFile()
    {
        $files = $this->getFieldModel();
        return $files ? $files->map(fn($filepond) => new UploadedFile(
            Storage::disk($this->tempDisk)->path($filepond->filepath),
            $filepond->filename,
            $filepond->mimetypes,
            UPLOAD_ERR_OK,
            true
        ))->toArray() : null;
    }

    public function getDataURL()
    {
        $files = $this->getFieldModel();
        return $files ? $files->map(fn($filepond) => 'data:' . $filepond->mimetypes . ';base64,' . base64_encode(Storage::disk($this->tempDisk)->get($filepond->filepath)))->toArray() : null;
    }

    public function copyTo(string $path, string $disk = '', string $visibility = '')
    {
        $files = $this->getFieldModel();
        if (!$files) return null;

        return $files->map(function ($filepond) use ($path, $disk, $visibility) {
            $to = $path . '-' . ($filepond->id);
            $storagePath = $to . '.' . $filepond->extension;
            $permanentDisk = $disk ?: $filepond->disk;
            Storage::disk($permanentDisk)->put($storagePath, Storage::disk($this->tempDisk)->get($filepond->filepath), $visibility);
            return ['url' => Storage::disk($permanentDisk)->url($storagePath)];
        })->toArray();
    }

    public function delete(): void
    {
        $files = $this->getFieldModel();
        if (!$files) return;

        $files->each(function ($filepond) {
            $method = $this->isSoftDeletable ? 'delete' : 'forceDelete';
            $filepond->$method();
            if (!$this->isSoftDeletable) {
                Storage::disk($this->tempDisk)->delete($filepond->filepath);
            }
        });
    }
}
