<?php

namespace Laltu\Quasar\Traits;

trait CacheKeys
{
    /**
     * Get access token cache key
     *
     * @param string $licenseKey
     * @return string
     */
    private function getAccessTokenKey(string $licenseKey): string
    {
        return "license:token-{$licenseKey}";
    }
}