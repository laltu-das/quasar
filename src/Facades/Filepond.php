<?php

namespace Laltu\Quasar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laltu\Quasar\FilepondManager field(string|array $field, bool $checkOwnership = true)
 * @method static \Laltu\Quasar\FilepondManager getFile()
 * @method static \Laltu\Quasar\FilepondManager getModel()
 * @method static \Laltu\Quasar\FilepondManager getDataURL()
 * @method static \Laltu\Quasar\FilepondManager copyTo(string $path, string $disk = '', string $visibility = '')
 * @method static \Laltu\Quasar\FilepondManager moveTo(string $path, string $disk = '', string $visibility = '')
 * @method static \Laltu\Quasar\FilepondManager delete()
 *
 * @see \Laltu\Quasar\FilepondManager
 */
class Filepond extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filepond';
    }
}