<?php

namespace Laltu\Quasar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Laltu\Quasar\Services\FilepondService;
use Throwable;

class FilepondController extends Controller
{
    public function process(Request $request, FilepondService $service)
    {
        if ($request->hasHeader('Upload-Length')) {
            return Response::make($service->initChunk(), 200, ['Content-Type' => 'text/plain']);
        }

        $validator = $service->validator($request, config('filepond.validation_rules', []));

        if ($validator->fails()) {
            return Response::make($validator->errors(), 422);
        }

        return Response::make($service->store($request), 200, ['Content-Type' => 'text/plain']);
    }

    public function patch(Request $request, FilepondService $service)
    {
        $offset = $service->chunk($request);
        return Response::make('Ok', 200, ['Upload-Offset' => $offset]);
    }

    public function head(Request $request, FilepondService $service)
    {
        if ($request->has('patch')) {
            $offset = $service->offset($request->patch);
            return Response::make('Ok', 200, ['Upload-Offset' => $offset]);
        }

        if ($request->has('restore')) {
            [$filepond, $content] = $service->restore($request->restore);
            return Response::make($content, 200, [
                'Access-Control-Expose-Headers' => 'Content-Disposition',
                'Content-Type' => $filepond->mimetypes,
                'Content-Disposition' => 'inline; filename="' . $filepond->filename . '"',
            ]);
        }

        return Response::make('Feature not implemented yet.', 406);
    }

    public function revert(Request $request, FilepondService $service)
    {
        $filepond = $service->retrieve($request->getContent());
        $service->delete($filepond);
        return Response::make('Ok', 200, ['Content-Type' => 'text/plain']);
    }
}
