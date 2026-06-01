<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\VehicleMappingRepositoryInterface;
use App\Repositories\Eloquent\VehicleMappingRepository;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
