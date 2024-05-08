<?php

namespace Laltu\Quasar\Concerns;

use Laltu\Quasar\Models\Filepond;

trait HasFilepond
{
    /**
     * User has many FilePond uploads
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileponds()
    {
        return $this->hasMany(config('filepond.model', Filepond::class), 'created_by');
    }
}