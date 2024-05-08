<?php

namespace Laltu\Quasar\Facades;
use Illuminate\Support\Facades\Facade;

class TokenBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        return 'shetabit-token-builder';
    }
}