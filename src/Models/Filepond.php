<?php

namespace Laltu\Quasar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filepond extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'filename',
        'filepath',
        'extension',
        'mimetypes',
        'disk',
        'expires_at',
    ];
}
