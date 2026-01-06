<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class N8nConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'webhook_url',
        'api_key',
        'is_active',
        'http_method',
        'timeout_ms',
        'retries',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
