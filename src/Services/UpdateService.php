<?php

namespace Laltu\Quasar\Services;

use ZipArchive;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Exception;
use FilesystemIterator;

class UpdateService
{
    protected string $updateInfoUrl = 'https://api.example.com/latest-version';
    protected string $updatePackageUrl = 'https://api.example.com/download-update';

    /**
     * Check for application updates and initiate the update process if necessary.
     *
     * @return bool Returns true if the update process completes successfully, false otherwise.
     */
    public function checkAndApplyUpdates(): bool
    {
        if ($this->checkForUpdates()) {
            return $this->downloadAndUpdate();
        }

        return false;
    }

    /**
     * Compare the current and latest versions to determine if an update is needed.
     *
     * @return bool Returns true if an update is needed, false otherwise.
     */
    protected function checkForUpdates(): bool
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->fetchLatestVersion();
        return version_compare($currentVersion, $latestVersion, '<');
    }

    /**
     * Retrieve the current version from the local configuration.
     *
     * @return string
     */
    protected function getCurrentVersion(): string
    {
        return config('app.version', '1.0.0');
    }

    /**
     * Fetch the latest version from a remote API.
     *
     * @return string
     */
    protected function fetchLatestVersion(): string
    {
        try {
            $response = Http::get($this->updateInfoUrl);
            return $response->json()['version'];
        } catch (Exception $e) {
            return $this->getCurrentVersion(); // Fail safely by returning the current version
        }
    }

    /**
     * Download the update package and apply it using a stream.
     *
     * @return bool
     */
    protected function downloadAndUpdate(): bool
    {
        try {
            $filePath = storage_path('app/update.zip');

            // Stream the download to handle large files
            $response = Http::withOptions([
                'sink' => $filePath  // Stream the download directly into a file
            ])->get($this->updatePackageUrl);

            // Check if the download was successful before proceeding
            if ($response->successful()) {
                // Extract and apply the update
                return $this->applyUpdate($filePath);
            } else {
                throw new Exception('Failed to download the update package.');
            }
        } catch (Exception $e) {
            // Log and handle the error
            return false;
        }
    }

    /**
     * Apply the downloaded update package.
     *
     * @param string $filePath Path to the update file.
     * @return bool
     */
    protected function applyUpdate(string $filePath): bool
    {
        if ($this->unzipAndReplaceFiles($filePath)) {
            try {
                // Run database migrations if part of the update
                Artisan::call('migrate', ['--force' => true]);

                // Cleanup after update
                Storage::delete($filePath);
                return true;
            } catch (Exception $e) {
                // Handle migration failures or other update errors
                return false;
            }
        }
        return false;
    }

    /**
     * Unzip the update package and replace the necessary files.
     *
     * @param string $filePath Path to the zip file containing the update.
     * @return bool Returns true if successful, false otherwise.
     */
    protected function unzipAndReplaceFiles(string $filePath): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) === TRUE) {
            $tempDir = storage_path('app/temp_update');
            $zip->extractTo($tempDir);
            $zip->close();

            $this->replaceFiles($tempDir, base_path());
            Storage::deleteDirectory('app/temp_update');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Replace the old files with the new ones from the update package.
     *
     * @param string $sourceDir Directory where the updated files are located.
     * @param string $targetDir The application base directory to update.
     * @return void
     */
    protected function replaceFiles(string $sourceDir, string $targetDir): void
    {
        $sourceIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($sourceIterator as $file) {
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $sourceIterator->getSubPathName();

            if ($file->isDir() && !is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            } else if ($file->isFile()) {
                copy($file, $targetPath);
            }
        }

        // Cleanup the old unused files
        $this->cleanupOldFiles($sourceDir, $targetDir);
    }

    /**
     * Cleanup old files that are not present in the new update.
     *
     * @param string $sourceDir Directory where the updated files are located.
     * @param string $targetDir The application base directory to update.
     * @return void
     */
    protected function cleanupOldFiles(string $sourceDir, string $targetDir): void
    {
        $newFiles = $this->getAllFiles($sourceDir);
        $oldFiles = $this->getAllFiles($targetDir);

        $filesToDelete = array_diff($oldFiles, $newFiles);

        foreach ($filesToDelete as $file) {
            unlink($targetDir . DIRECTORY_SEPARATOR . $file);
        }
    }

    /**
     * Get all file paths recursively from a directory.
     *
     * @param string $directory Directory to scan
     * @return array Array of file paths
     */
    protected function getAllFiles(string $directory): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $iterator->getSubPathName();
            }
        }

        return $files;
    }
}
