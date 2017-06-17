<?php

namespace CanvasImporter;

use Illuminate\Support\ServiceProvider;

class CanvasImporterServiceProvider extends ServiceProvider
{
    /** Bootstrap the application services. */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\Import::class,
            ]);
        }
    }

    /** Register the application services. */
    public function register()
    {
        //
    }
}
