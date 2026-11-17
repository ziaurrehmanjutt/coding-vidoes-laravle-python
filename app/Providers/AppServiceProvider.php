<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPythonVideoService();
    }

    /**
     * Start the Python video service together with `php artisan dev` / `composer run dev`.
     */
    private function registerPythonVideoService(): void
    {
        if (! $this->app->runningInConsole() || ! is_dir(base_path('python_services/.venv'))) {
            return;
        }

        $python = PHP_OS_FAMILY === 'Windows'
            ? '.venv\\Scripts\\python.exe'
            : '.venv/bin/python';

        DevCommands::register(
            "cd python_services && {$python} -m uvicorn video_service.main:app --host 127.0.0.1 --port 8001",
            'python',
        );
    }
}
