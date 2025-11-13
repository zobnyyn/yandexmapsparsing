<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\YandexMapsService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(YandexMapsService::class);
    }

    public function boot(): void
    {
    }
}

