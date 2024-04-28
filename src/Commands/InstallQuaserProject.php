<?php

namespace Laltu\Quasar\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallQuaserProject extends Command implements PromptsForMissingInput
{
    protected $name = 'install:quaser-project';

    protected $description = 'Downloads and installs a project from Envato given a purchase code.';

    public function handle(): void
    {
        try {

            $this->verifyEnvatoLicense();

            $this->info('Quasar project installation complete!');

        } catch (\Exception $e) {
            $this->error("Installation failed: {$e->getMessage()}");
        }
    }


    private function verifyEnvatoLicense(): void
    {
        $response = Http::acceptJson()->post(config('quasar.license_server_url')."/api/license-verify", [
            'envatoItemId' => $this->argument('envato-item-id'),
            'licenseKey' => $this->argument('envato-purchase-code'),
        ]);

        if (!$response->successful()) {
            $this->error("API call error: {$response->json('message')}"); // Assuming API error message
            return;
        }

        // Additional validation of the API response if needed
        $this->info($response->json('message'));

    }


    private function runShellCommands(array $commands): void
    {
        foreach ($commands as $command) {

            $process = Process::run($command);

            $this->info($process->output());
        }
    }

    /**
     * Get the options for the command.
     *
     * @return array An array of options for the command.
     */
    public function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'Specify the database driver'],
            ['force', 'f', InputOption::VALUE_OPTIONAL, 'Force install if the directory exists'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['envato-item-id', InputArgument::REQUIRED, 'Envato Item ID'],
            ['envato-purchase-code', InputArgument::REQUIRED, 'Envato Purchase Code'],
            ['version', InputArgument::REQUIRED, 'Envato Purchase Code'],
        ];
    }

    protected function replaceInFile(string $file, $search, $replace): void
    {
        File::put($file, Str::replace($search, $replace, File::get($file)));
    }

}

