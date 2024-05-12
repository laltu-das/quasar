<?php

namespace Laltu\Quasar\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Models\Filepond;
use RuntimeException;

class FilepondService
{
    private string $tempFolder = 'filepond';

    /**
     * Stores the uploaded file and creates a Filepond record.
     *
     * @param Request $request
     * @return string Encrypted file ID
     */
    public function store(Request $request): string
    {
        $file = $request->file('file');
        if (!$file) {
            throw new RuntimeException("No file uploaded.");
        }

        $path = $file->store($this->tempFolder, 's3', 'public');

        $filepond = Filepond::create([
            'filepath' => $path,
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetypes' => $file->getClientMimeType(),
            'disk' => 's3',
            'filesize' => $file->getSize(),
            'expires_at' => now()->addMinutes(30),
        ]);

        return Crypt::encryptString($filepond->id);
    }

    /**
     * Fetches the file content from a URL.
     *
     * @param string $url
     * @return string File content
     */
    public function fetch(string $url): string
    {
        $contents = file_get_contents($url);
        if ($contents === false) {
            throw new RuntimeException("Failed to fetch the file.");
        }
        return $contents;
    }

    /**
     * Restores a file based on its ID.
     *
     * @param int $id
     * @return string File content
     */
    public function restore(int $id): string
    {
        $filepond = Filepond::findOrFail($id);
        return Storage::disk('s3')->get($filepond->filepath);
    }

    /**
     * Deletes a file based on its ID.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $filepond = Filepond::findOrFail(Crypt::decryptString($id));
        Storage::disk('s3')->delete($filepond->filepath);
        return $filepond->delete();
    }

    /**
     * Loads a file based on its ID.
     *
     * @param string $id
     * @return string File content
     */
    public function load(string $id): string
    {
        $filepond = Filepond::findOrFail(Crypt::decryptString($id));
        return Storage::disk('s3')->get($filepond->filepath);
    }

    /**
     * Process file upload and return file ID for FilePond.
     *
     * @param Request $request
     * @param string $fieldName
     * @return string File ID
     */
    public function processFile(Request $request, string $fieldName): string
    {
        $file = $request->file($fieldName);
        if (!$file) {
            throw new RuntimeException("No file uploaded under field: {$fieldName}");
        }

        $path = $file->store($this->tempFolder, 's3');
        $filepond = new Filepond([
            'filepath' => $path,
            'filename' => $file->getClientOriginalName(),
            'mimetype' => $file->getClientMimeType(),
            'filesize' => $file->getSize(),
        ]);
        $filepond->save();

        return Crypt::encryptString($filepond->id);
    }
}
