<?php

namespace Laltu\Quasar\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Laltu\Quasar\Services\EnvironmentManager;
use Laltu\Quasar\Services\InstallationService;
use Laltu\Quasar\Services\PermissionsChecker;
use Illuminate\Routing\Controller;
use Symfony\Component\Console\Output\BufferedOutput;
use ZipArchive;

class InstallationController extends Controller
{
    public function gettingStarted()
    {
        return Inertia::render('GettingStarted');
    }

    public function showServerRequirements()
    {
        $requirements = [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'BCMath PHP Extension' => extension_loaded('bcmath'),
            'Ctype PHP Extension' => extension_loaded('ctype'),
            'Fileinfo PHP Extension' => extension_loaded('fileinfo'),
            'JSON PHP Extension' => extension_loaded('json'),
            'Mbstring PHP Extension' => extension_loaded('mbstring'),
            'OpenSSL PHP Extension' => extension_loaded('openssl'),
            'PDO PHP Extension' => extension_loaded('pdo'),
            'Tokenizer PHP Extension' => extension_loaded('tokenizer'),
            'XML PHP Extension' => extension_loaded('xml'),
        ];

        return Inertia::render('ServerRequirements', ['requirements' => $requirements]);
    }

    public function showFolderPermissions(PermissionsChecker $permissionsChecker)
    {
        $permissions = $permissionsChecker->check(
            config('installer.permissions')
        );

        return Inertia::render('FolderPermissions', $permissions);
    }

    public function showEnvironmentVariables(EnvironmentManager $environmentManager)
    {
        $environments = $environmentManager->getEnvContent();

        return Inertia::render('EnvironmentVariables', compact('environments'));
    }

    public function showEnvatoLicense()
    {
        return Inertia::render('EnvatoLicense', [
            'licenseServerUrl' => config('quasar.license_server_url'),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    public function submitEnvatoLicense(Request $request, InstallationService $installationService)
    {
        $request->validate([
            'envatoItemId' => 'required',
            'licenseKey' => 'required',
            'environments' => 'nullable',
        ]);

        // Attempt to verify the license via external API
        $response = Http::acceptJson()->post(config('quasar.license_server_url') . "/api/license-verify", [
            'envatoItemId' =>$request->envatoItemId,
            'licenseKey' => $request->licenseKey,
            'version' => config('quasar.version') ,
        ]);

        if ($response->failed()) {
            return response()->json($response->json(), 422);
        }

        // Handling the ZIP file response
        $zipContent = $response->body();
        $zipPath = storage_path('app/project-file.zip');
        Storage::disk('local')->put('project-file.zip', $zipContent);

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo(base_path());
                $zip->close();
            } else {
                throw new \Exception("Failed to open ZIP file for extraction.");
            }
        } finally {
            Storage::disk('local')->delete('project-file.zip');
        }

        // Prepare installation details and lock installation
        $installationService->createInstallationLock([]);

        // Run migrations and capture the output
        $output = $this->runArtisanCommands();

        return redirect()->route('install.installation-progress')->with('success', $output);
    }

    protected function runArtisanCommands()
    {
        $process = Process::run(['php artisan migrate'], base_path());

        if (!$process->successful()) {
            throw new ProcessFailedException($process);
        }

        return $process->output();
    }

}
