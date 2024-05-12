<?php

namespace Laltu\Quasar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laltu\Quasar\Services\FilepondService;

class FilepondController extends Controller
{
    protected FilepondService $filepondService;

    public function __construct(FilepondService $filepondService)
    {
        $this->filepondService = $filepondService;
    }

    // Process file upload
    public function process(Request $request)
    {
        try {
            $fileId = $this->filepondService->store($request);
            return response()->json(['id' => $fileId]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Fetch file for FilePond input
    public function fetch(Request $request)
    {
        try {
            // Fetch the file using a URL provided by FilePond (from a remote source)
            $fileContent = $this->filepondService->fetch($request->input('url'));
            return response($fileContent);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Restore file from server
    public function restore(Request $request, $id)
    {
        try {
            $fileContent = $this->filepondService->restore($id);
            return response($fileContent);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Revert upload (delete uploaded file)
    public function revert(Request $request)
    {
        try {
            $this->filepondService->delete($request->input('id'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Remove file
    public function remove(Request $request)
    {
        try {
            $this->filepondService->delete($request->input('id'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Load file from server
    public function load(Request $request, $id)
    {
        try {
            $fileContent = $this->filepondService->load($id);
            return response($fileContent);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
