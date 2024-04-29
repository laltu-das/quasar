<?php

namespace Laltu\Quasar\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallEnvatoProject extends Command implements PromptsForMissingInput
{
    protected $name = 'install:envato-project';

    protected $description = 'Downloads and installs a project from Envato given a purchase code.';

    public function handle(): void
    {
        try {
            $progressBar = $this->output->createProgressBar();
//
            $progressBar->start();
            $this->verifyLicenseAndDownload();
            $progressBar->finish();
            $this->info('Project installation complete!');
        } catch (\Exception $e) {
            $this->error("Installation failed: {$e->getMessage()}");
        }
    }

    private function verifyLicenseAndDownload(): void
    {
        $response = Http::acceptJson()->post("https://support.scriptspheres.com/api/license-verify", [
            'envatoItemId' => config('envato.item_id')??$this->argument('envato-item-id'),
            'licenseKey' => config('envato.purchase_code')??$this->argument('envato-purchase-code'),
            'version' => config('envato.version'),
        ]);

        if (!$response->successful()) {
            $this->error("Error: {$response->json('message')}");
            return;
        }


        $this->downloadFile($response);
    }

    private function downloadFile($response): void
    {
        $filePath = "project/project.zip";

        Storage::disk('local')->put($filePath, $response->body());

        $this->info('File downloaded and saved successfully.');

        $this->extractZipFile($filePath);
    }

    private function extractZipFile($zipFilePath): void
    {
        $fullZipPath = storage_path('app/' . $zipFilePath);

        Process::fromShellCommandline("unzip -o $fullZipPath -d " . base_path());

        $this->info('Extracting file...');

        $this->info('File extracted successfully.');

        Storage::disk('local')->delete($zipFilePath);
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
        ];
    }

    protected function replaceInFile(string $file, $search, $replace): void
    {
        File::put($file, Str::replace($search, $replace, File::get($file)));
    }

}

