<?php

namespace Apsonex\Media;

use Apsonex\Media\Factory\Media;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    const CONFIG_PATH = __DIR__ . '/../config/media.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('media.php'),
        ], 'media');
    }


    public function register()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'media');

        $this->app->bind('media', function () {
            return new Media();
        });
    }

}