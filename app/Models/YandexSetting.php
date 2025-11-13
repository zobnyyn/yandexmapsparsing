<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YandexSetting extends Model
{
    protected $fillable = [
        'user_id',
        'yandex_url',
        'cached_data',
        'last_sync'
    ];

    protected $casts = [
        'cached_data' => 'array',
        'last_sync' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

