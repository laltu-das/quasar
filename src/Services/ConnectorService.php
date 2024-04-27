<?php

namespace Laltu\Quasar\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laltu\Quasar\Exceptions\LicenseException;
use Laltu\Quasar\Traits\CacheKeys;

class ConnectorService
{
    use CacheKeys;

    public string $license;

    private string $licenseKey;
    private string $accessToken;

    /**
     * @throws LicenseException
     * @throws ConnectionException
     */
    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;

        $this->accessToken = $this->getAccessToken($licenseKey);
    }

    /**
     * Check license status
     *
     * @param array $data
     *
     * @return boolean
     * @throws ConnectionException
     */
    public function validateLicense(array $data = []): bool
    {
        if ($this->accessToken) {
            $url = config('license-connector.license_server_url') . '/api/license-server/license';

            $response = Http::withHeaders([
                'x-host' => config('app.url'),
                'x-host-name' => config('app.name'),
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $data);

            if ($response->ok()) {
                $license = $response->json();

                $this->license = $license;

                return $license && $license['status'] == 'active';
            }
        }

        return false;
    }

    /**
     * Get access token for the given domain
     *
     * @param string $licenseKey
     *
     * @return string|null
     * @throws ConnectionException
     * @throws LicenseException
     */
    private function getAccessToken(string $licenseKey): null | string
    {
        $accessTokenCacheKey = $this->getAccessTokenKey($licenseKey);

        $accessToken = Cache::get($accessTokenCacheKey, null);

        if ($accessToken) {
            return $accessToken;
        }

        $url = config('license-connector.license_server_url') . '/api/license-server/auth/login';

        $response = Http::withHeaders([
            'x-host' => config('app.url'),
            'x-host-name' => config('app.name'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, [            'license_key' => $licenseKey        ]);

        $data = $response->json();

        if ($response->ok()) {
            if ($data['status'] === true) {
                if (!empty($data['access_token'])) {
                    $accessToken = $data['access_token'];

                    Cache::put($accessTokenCacheKey, $accessToken, now()->addMinutes(60));

                    return $accessToken;
                } else {
                    throw new LicenseException($data['message']);
                }
            }
        }

        throw new LicenseException($data['message']);
    }
}