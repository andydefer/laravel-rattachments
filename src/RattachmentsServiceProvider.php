<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments;

use AndyDefer\LaravelRattachments\Contracts\Repositories\RattachmentRepositoryInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\RattachmentServiceInterface;
use AndyDefer\LaravelRattachments\Repositories\RattachmentRepository;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use Illuminate\Support\ServiceProvider;

final class RattachmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository
        $this->app->singleton(RattachmentRepository::class, function ($app) {
            return new RattachmentRepository;
        });

        $this->app->bind(
            RattachmentRepositoryInterface::class,
            RattachmentRepository::class
        );

        // Service
        $this->app->singleton(RattachmentService::class, function ($app) {
            return new RattachmentService(
                $app->make(RattachmentRepositoryInterface::class)
            );
        });

        $this->app->bind(
            RattachmentServiceInterface::class,
            RattachmentService::class
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'rattachments-migrations');
    }
}
