<?php

namespace Laltu\Quasar;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Models\Filepond;
use const UPLOAD_ERR_OK;

class FilepondManager
{
    private $fieldValue;

    private $tempDisk;

    private $isMultipleUpload;

    private $fieldModel;

    private $isOwnershipAware;

    private $isSoftDeletable;

    /**
     * @return string
     */
    public function getTempDisk()
    {
        return $this->tempDisk;
    }

    /**
     * @return $this
     */
    public function setTempDisk(string $tempDisk)
    {
        $this->tempDisk = $tempDisk;

        return $this;
    }

    /**
     * Get the filepond database model for the FilePond field
     *
     * @return mixed
     */
    protected function getFieldModel()
    {
        return $this->fieldModel;
    }

    /**
     * Set the FilePond model from the field
     *
     * @return $this
     */
    protected function setFieldModel(string $model)
    {
        if (!$this->getFieldValue()) {
            $this->fieldModel = null;

            return $this;
        }

        if ($this->getIsMultipleUpload()) {
            $this->fieldModel = $model::when($this->isOwnershipAware, function ($query) {
                $query->owned();
            })
                ->whereIn('id', (new Collection($this->getFieldValue()))->pluck('id'))
                ->get();

            return $this;
        }

        $input = $this->getFieldValue();
        $this->fieldModel = $model::when($this->isOwnershipAware, function ($query) {
            $query->owned();
        })
            ->where('id', $input['id'])
            ->first();

        return $this;
    }

    /**
     * Decrypt the FilePond field value data
     *
     * @return array
     */
    protected function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set the FilePond field value data
     *
     * @return $this
     */
    protected function setFieldValue(string|array|null $fieldValue)
    {
        if (!$fieldValue) {
            $this->fieldValue = null;

            return $this;
        }

        $this->isMultipleUpload = is_array($fieldValue);

        if ($this->getIsMultipleUpload()) {
            if (!$fieldValue[0]) {
                $this->fieldValue = null;

                return $this;
            }

            $this->fieldValue = array_map(function ($input) {
                return $this->decrypt($input);
            }, $fieldValue);

            return $this;
        }

        $this->fieldValue = $this->decrypt($fieldValue);

        return $this;
    }

    /**
     * @return bool
     */
    protected function getIsMultipleUpload()
    {
        return $this->isMultipleUpload;
    }

    /**
     * Decrypt the FilePond field value data
     *
     * @return mixed
     */
    protected function decrypt(string $data)
    {
        return Crypt::decrypt($data, true);
    }

    /**
     * Get the soft delete from filepond config
     *
     * @return bool
     */
    protected function getIsSoftDeletable()
    {
        return $this->isSoftDeletable;
    }

    /**
     * Set the soft delete value from filepond config
     *
     * @return $this
     */
    protected function setIsSoftDeletable(bool $isSoftDeletable)
    {
        $this->isSoftDeletable = $isSoftDeletable;

        return $this;
    }

    /**
     * Get the ownership check value for filepond model
     *
     * @return bool
     */
    protected function getIsOwnershipAware()
    {
        return $this->isOwnershipAware;
    }

    /**
     * Set the ownership check value for filepond model
     *
     * @return $this
     */
    protected function setIsOwnershipAware(bool $isOwnershipAware)
    {
        $this->isOwnershipAware = $isOwnershipAware;

        return $this;
    }

    /**
     * Create file object from filepond model
     *
     * @return UploadedFile
     */
    protected function createFileObject(Filepond $filepond)
    {
        return new UploadedFile(
            Storage::disk($this->tempDisk)->path($filepond->filepath),
            $filepond->filename,
            $filepond->mimetypes,
            UPLOAD_ERR_OK,
            true
        );
    }

    /**
     * Create Data URL from filepond model
     * More at - https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs
     *
     * @return string
     *
     */
    protected function createDataUrl(Filepond $filepond)
    {
        return 'data:' . $filepond->mimetypes . ';base64,' . base64_encode(Storage::disk($this->tempDisk)->get($filepond->filepath));
    }

    /**
     * Set the FilePond field name
     *
     * @return $this
     */
    public function field(string|array|null $field, bool $checkOwnership = true)
    {
        $this->setFieldValue($field)
            ->setTempDisk(config('filepond.temp_disk', 'local'))
            ->setIsSoftDeletable(config('filepond.soft_delete', true))
            ->setIsOwnershipAware($checkOwnership)
            ->setFieldModel(config('filepond.model', Filepond::class));

        return $this;
    }

    /**
     * Return file object from the field
     *
     * @return array|UploadedFile
     */
    public function getFile()
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            return $this->getFieldModel()->map(function ($filepond) {
                return $this->createFileObject($filepond);
            })->toArray();
        }

        return $this->createFileObject($this->getFieldModel());
    }

    /**
     * Get the filepond file as Data URL string
     * More at - https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs
     *
     * @return array|string
     *
     * @throws FileNotFoundException
     */
    public function getDataURL()
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            return $this->getFieldModel()->map(function ($filepond) {
                return $this->createDataUrl($filepond);
            })->toArray();
        }

        return $this->createDataUrl($this->getFieldModel());
    }

    /**
     * Get the filepond database model for the FilePond field
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->getFieldModel();
    }

    /**
     * Copy the FilePond files to destination
     *
     * @return array
     *
     */
    public function copyTo(string $path, string $disk = '', string $visibility = '')
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path . '-' . ($index + 1);
                $response[] = $this->putFile($filepond, $to, $disk, $visibility);
            }

            return $response;
        }

        $filepond = $this->getFieldModel();

        return $this->putFile($filepond, $path, $disk, $visibility);
    }

    /**
     * Put the file in permanent storage and return response
     *
     * @return array
     *
     */
    private function putFile(Filepond $filepond, string $path, string $disk, string $visibility)
    {
        $permanentDisk = $disk == '' ? $filepond->disk : $disk;

        Storage::disk($permanentDisk)->put($path . '.' . $filepond->extension, Storage::disk($this->getTempDisk())->get($filepond->filepath), $visibility);

        return [
            'id' => $filepond->id,
            'dirname' => dirname($path . '.' . $filepond->extension),
            'basename' => basename($path . '.' . $filepond->extension),
            'extension' => $filepond->extension,
            'filename' => basename($path . '.' . $filepond->extension, '.' . $filepond->extension),
            'location' => $path . '.' . $filepond->extension,
            'url' => Storage::disk($permanentDisk)->url($path . '.' . $filepond->extension),
        ];
    }

    /**
     * Copy the FilePond files to destination and delete
     *
     * @return array
     *
     */
    public function moveTo(string $path, string $disk = '', string $visibility = '')
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path . '-' . ($index + 1);
                $response[] = $this->putFile($filepond, $to, $disk, $visibility);
            }
            $this->delete();

            return $response;
        }

        $filepond = $this->getFieldModel();
        $response = $this->putFile($filepond, $path, $disk, $visibility);
        $this->delete();

        return $response;
    }

    /**
     * Delete files related to FilePond field
     *
     * @return void
     */
    public function delete()
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                if ($this->getIsSoftDeletable()) {
                    $filepond->delete();
                } else {
                    Storage::disk($this->getTempDisk())->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }

            return;
        }

        $filepond = $this->getFieldModel();
        if ($this->getIsSoftDeletable()) {
            $filepond->delete();
        } else {
            Storage::disk($this->getTempDisk())->delete($filepond->filepath);
            $filepond->forceDelete();
        }
    }
}
