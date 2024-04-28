<?php

namespace Laltu\Quasar\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class IndexController
{
    public function __invoke()
    {
        // Check if the application is already installed
        if (Storage::exists('installed.lock')) {
            return redirect()->route('home');
        }

        return view('quasar::layout', [
            'quasarScriptVariables' => [
                'appName' => config('app.name'),
                'licenseServerUrl' => config('quasar.license_server_url'),
            ],
        ]);
    }
}
