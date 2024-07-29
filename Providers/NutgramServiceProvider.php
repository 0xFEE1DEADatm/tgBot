<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NutgramServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Публикуем конфигурационный файл
        $this->publishes([
            __DIR__ . '/../../vendor/nutgram/laravel/config/nutgram.php' => config_path('nutgram.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Объединяем конфигурационные файлы
        $this->mergeConfigFrom(
            __DIR__ . '/../../vendor/nutgram/laravel/config/nutgram.php',
            'nutgram'
        );
    }
}
