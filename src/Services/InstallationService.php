<?php

namespace Laltu\Quasar\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class InstallationService
{
    /**
     * Create or update the installed.lock file with enhanced installation details.
     * Encrypts the data for security and includes additional system details.
     *
     * @param array $details
     * @return void
     */
    public function createInstallationLock(array $details): void
    {
        $data = [
            'installed_on' => now()->toDateTimeString(),
            'app_version' => $details['app_version'],
            'database_name' => $details['database_name'],
            'database_type' => Config::get('database.default'),
            'environment' => app()->environment(),
            'php_version' => phpversion(),
            'os' => php_uname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'additional_info' => $details['additional_info'] ?? 'No additional info provided',
        ];

        // Encrypt data before saving to ensure privacy and data integrity
        $encryptedData = Crypt::encryptString(json_encode($data));
        Storage::disk('local')->put('installed.lock', $encryptedData);
    }

    /**
     * Retrieve decrypted data from the installed.lock file.
     *
     * @return array|null
     */
    public function getInstallationData(): ?array
    {
        if (Storage::disk('local')->exists('installed.lock')) {
            $encryptedData = Storage::disk('local')->get('installed.lock');
            $data = Crypt::decryptString($encryptedData);
            return json_decode($data, true);
        }

        return null;
    }

    /**
     * Validate installation by sending installation data to an API server.
     *
     * @return bool Returns true if the installation is valid, false otherwise.
     * @throws ConnectionException
     */
    public function validateInstallation(): bool
    {
        $apiUrl = "";
        $data = $this->getInstallationData();
        if ($data === null) {
            return false;
        }

        // Encrypt the installation data for security
        $encryptedData = Crypt::encryptString(json_encode($data));

        // Send the encrypted data to the API server
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($apiUrl, ['encrypted_data' => $encryptedData]);

        // Check if the API server confirms that the installation is valid
        return $response->successful() && $response->json()['is_valid'] === true;
    }
}
