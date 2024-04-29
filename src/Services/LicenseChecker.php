<?php

namespace Laltu\Quasar\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laltu\Quasar\Exceptions\IpAddressNotFoundException;
use Laltu\Quasar\Exceptions\LicenseException;
use Laltu\Quasar\Traits\CacheKeys;
use Pdp\CannotProcessHost;

class LicenseChecker
{
    use CacheKeys;

    private string $licenseKey;

    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;
    }

    /**
     * Validate license key against the server and store license data if valid.
     *
     * @param array $data Data to send to license server
     * @return boolean True if the license is active, false otherwise
     * @throws ConnectionException If connection fails
     */
    public function validateLicense(array $data = []): bool
    {
        $url = 'https://scriptspheres.com/license';

        $response = Http::withHeaders([
            'X-Host' => config('app.url'),
            'X-Host-Name' => config('app.name'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $data);

        if ($response->ok()) {
            $license = null;
            $license = $response->json();
            return $license && $license['status'] == 'active';
        }

        return false;
    }


    /**
     * Determine if the client is connecting from a localhost IP address.
     *
     * @return bool True if localhost, false otherwise
     */
    public static function isLocalhost(): bool
    {
        return in_array(request()->ip(), ['127.0.0.1', '::1', '172.27.0.1']);
    }

    /**
     * Retrieve the public IP address of a client connecting from localhost.
     *
     * @return string|null The public IP address if retrievable, null otherwise
     * @throws ConnectionException If network issues prevent retrieval
     */
    public static function getPublicIp(): ?string
    {
        $cacheKey = 'ultimate-support:ip:local_public_ip';
        if (Config::get('ultimate-support.ip.use_cache_for_local_public_ip', true)) {
            return Cache::remember($cacheKey, now()->addDay(), function () {
                $response = Http::withoutVerifying()->get('https://api.ipify.org?format=json');
                return $response->ok() ? $response->json()['ip'] ?? null : null;
            });
        }

        $response = Http::withoutVerifying()->get('https://api.ipify.org?format=json');
        return $response->ok() ? $response->json()['ip'] ?? null : null;
    }

    /**
     * Get the client's real IP address, considering various server proxies and headers.
     *
     * @param bool $getLocalPublicIp Whether to retrieve the public IP for localhost
     * @return array|null The client IP data, or null if an error occurs
     * @throws IpAddressNotFoundException If the IP address cannot be determined
     */
    public static function getIpAddress(bool $getLocalPublicIp = false): ?array
    {
        $baseIpAddress = request()->ip();

        try {
            if ($getLocalPublicIp && self::isLocalhost()) {
                return [
                    'is_local' => true,
                    'base_ip' => $baseIpAddress,
                    'ip_address' => self::getPublicIp(),
                ];
            }

            // Process potential headers for real IP determination
            $ipAddress = $baseIpAddress;
            if (filter_var($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '', FILTER_VALIDATE_IP)) {
                $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
            } elseif (filter_var($_SERVER['HTTP_X_REAL_IP'] ?? '', FILTER_VALIDATE_IP)) {
                $ipAddress = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (filter_var($_SERVER['HTTP_CLIENT_IP'] ?? '', FILTER_VALIDATE_IP)) {
                $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }

            return [
                'is_local' => false,
                'base_ip' => $baseIpAddress,
                'ip_address' => $ipAddress
            ];
        } catch (Exception $e) {
            Log::error('Error getting IP address: ' . $e->getMessage());
            throw new IpAddressNotFoundException('Unable to determine IP address.', 0, $e);
        }
    }

    /**
     * Comprehensive check of the license against domain and IP conditions.
     *
     * @param string $encryptedLicense The encrypted license string
     * @return bool True if the license is valid according to all conditions, false otherwise
     * @throws CannotProcessHost
     */
    public function checkLicense(string $encryptedLicense): bool
    {
        try {
            $licenseData = Crypt::decrypt($encryptedLicense);
            $resolvedDomain = $this->validateDomain(request()->getHost());
            $ipData = $this->getIpAddress(true);

            if ($resolvedDomain->registrableDomain() !== $licenseData['domain'] ||
                $ipData['ip_address'] !== $licenseData['ip_address'] ||
                now()->greaterThan($licenseData['expires_at'])) {
                throw new LicenseException('Invalid license for the current domain or IP.');
            }

            return true;
        } catch (Exception $e) {
            Log::error('License validation failed: ' . $e->getMessage());
            return false;
        }
    }
}

