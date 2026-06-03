<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use App\Repositories\VehicleMappingRepository;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Repositories\QuoteRepository;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Services\QuoteService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the Mapping Interface to its Eloquent Implementation
        $this->app->bind(
            VehicleMappingRepositoryInterface::class,
            VehicleMappingRepository::class
        );

        $this->app->bind(QuoteRepositoryInterface::class, QuoteRepository::class);
        $this->app->bind(QuoteServiceInterface::class, QuoteService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
