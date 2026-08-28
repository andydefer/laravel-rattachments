<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments;

use AndyDefer\LaravelRattachments\Contracts\Repositories\RattachmentRepositoryInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\ConstraintDiscoveryServiceInterface;
use AndyDefer\LaravelRattachments\Contracts\Services\RattachmentServiceInterface;
use AndyDefer\LaravelRattachments\Repositories\RattachmentRepository;
use AndyDefer\LaravelRattachments\Services\ConstraintDiscoveryService;
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Support\ServiceProvider;
use PhpParser\Parser;
use PhpParser\ParserFactory;

final class RattachmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ============================================================
        // REPOSITORY
        // ============================================================

        $this->app->singleton(RattachmentRepository::class, function ($app) {
            return new RattachmentRepository;
        });

        $this->app->bind(
            RattachmentRepositoryInterface::class,
            RattachmentRepository::class
        );

        // ============================================================
        // SERVICE
        // ============================================================

        $this->app->singleton(RattachmentService::class, function ($app) {
            return new RattachmentService(
                $app->make(RattachmentRepositoryInterface::class)
            );
        });

        $this->app->bind(
            RattachmentServiceInterface::class,
            RattachmentService::class
        );

        // ============================================================
        // CONSTRAINT DISCOVERY SERVICE
        // ============================================================

        $this->app->singleton(ConstraintDiscoveryService::class, function ($app) {
            return new ConstraintDiscoveryService(
                $app->make(FileSystemInterface::class),
                $app->make(Parser::class)
            );
        });

        $this->app->bind(
            ConstraintDiscoveryServiceInterface::class,
            ConstraintDiscoveryService::class
        );

        // ============================================================
        // DEPENDENCIES
        // ============================================================

        $this->app->singleton(FileSystemInterface::class, function () {
            return new FileSystemService;
        });

        $this->app->singleton(Parser::class, function () {
            return (new ParserFactory)->createForNewestSupportedVersion();
        });
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
