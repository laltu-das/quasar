<?php

namespace Laltu\Quasar\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laltu\Quasar\Models\Filepond;

class FilepondService
{
    private $disk;
    private $tempDisk;
    private $tempFolder;

    public function __construct()
    {
        $this->disk = config('filepond.disk', 'public');
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
    }

    public function validator(Request $request, array $rules)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return Validator::make($request->all(), [$field => $rules]);
    }

    public function store(Request $request): string
    {
        $file = $request->file(array_key_first(Arr::dot($request->all())));

        $filepond = Filepond::create([
            'filepath' => $file->store($this->tempFolder, $this->tempDisk),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetypes' => $file->getClientMimeType(),
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    public function initChunk(): string
    {
        $filepond = Filepond::create([
            'filepath' => '', 'filename' => '', 'extension' => '', 'mimetypes' => '',
            'disk' => $this->disk, 'created_by' => auth()->id(), 'expires_at' => now()->addMinutes(config('filepond.expiration', 30))
        ]);

        Storage::disk($this->tempDisk)->makeDirectory($this->tempFolder . '/' . $filepond->id);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    public function chunk(Request $request): int|string
    {
        $id = Crypt::decrypt($request->patch)['id'];
        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder . '/' . $id . '/');
        $filename = $request->header('Upload-Name');
        file_put_contents($dir . $request->header('Upload-Offset'), $request->getContent());

        $this->consolidateChunks($dir, $filename, $request->header('Upload-Length'));

        $filepond = Filepond::where('id', $id)->firstOrFail();
        $filepath = $this->tempFolder . '/' . $id . '/' . $filename;
        $filepond->update([
            'filepath' => $filepath,
            'filename' => $filename,
            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'mimetypes' => Storage::disk($this->tempDisk)->mimeType($filepath),
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        return $filepond->id;
    }

    private function consolidateChunks($dir, $filename, $length): void
    {
        $file = fopen($dir . $filename, 'w');
        foreach (glob($dir . '*') as $chunk) {
            $offset = basename($chunk);
            $chunkContent = file_get_contents($chunk);
            fseek($file, $offset);
            fwrite($file, $chunkContent);
            unlink($chunk);
        }
        fclose($file);
    }

    public function retrieve(string $content)
    {
        return Filepond::findOrFail(Crypt::decrypt($content)['id']);
    }

    public function delete(Filepond $filepond): ?bool
    {
        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk($this->tempDisk)->delete($filepond->filepath);
        Storage::disk($this->tempDisk)->deleteDirectory($this->tempFolder . '/' . $filepond->id);

        return $filepond->forceDelete();
    }

    public function offset(string $content): int
    {
        $filepond = $this->retrieve($content);
        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder . '/' . $filepond->id . '/');
        $size = 0;
        $chunks = glob($dir . '*');
        foreach ($chunks as $chunk) {
            $size += filesize($chunk);
        }

        return $size;
    }

    public function restore(string $content): array
    {
        $filepond = $this->retrieve($content);
        $filepath = $this->tempFolder . '/' . $filepond->id . '/' . $filepond->filename;
        $content = Storage::disk($this->tempDisk)->get($filepath);

        return [$filepond, $content];
    }
}
