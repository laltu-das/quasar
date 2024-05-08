<?php

namespace Laltu\Quasar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UrlToken extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'token',
        'expired_at',
        'usage_count',
        'max_usage_limit',
        'data',
        'type',
        'tokenable',
    ];
}
