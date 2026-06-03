<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\VehicleMappingRepositoryInterface;
use App\Repositories\Eloquent\VehicleMappingRepository;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Eloquent\QuoteRepository;
use App\Services\Contracts\QuoteServiceInterface;
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
