<?php

namespace Laltu\Quasar\Facades;

use Illuminate\Support\Facades\Facade;
use Laltu\Quasar\FilepondManager;

/**
 * @method static FilepondManager field(string|array $field, bool $checkOwnership = true)
 * @method static FilepondManager getFile()
 * @method static FilepondManager getModel()
 * @method static FilepondManager getDataURL()
 * @method static FilepondManager copyTo(string $path, string $disk = '', string $visibility = '')
 * @method static FilepondManager moveTo(string $path, string $disk = '', string $visibility = '')
 * @method static FilepondManager delete()
 *
 * @see FilepondManager
 */
class Filepond extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return FilepondManager::class;
    }
}