<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use App\Repositories\VehicleMappingRepository;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Repositories\QuoteRepository;
use App\Services\InsuranceService;
use App\Services\Interfaces\InsuranceServiceInterface;
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

        // Bind Proposal interface & repository
        $this->app->bind(\App\Repositories\Interfaces\ProposalRepositoryInterface::class, \App\Repositories\ProposalRepository::class);
        $this->app->bind(\App\Services\Interfaces\ProposalServiceInterface::class, \App\Services\ProposalService::class);

        // Binding Insurer interface and repository
        $this->app->bind(\App\Repositories\Interfaces\InsurerRepositoryInterface::class, \App\Repositories\InsurerRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\UsersInsuranceRepositoryInterface::class, \App\Repositories\UsersInsuranceRepository::class);

        $this->app->bind(\App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface::class, \App\Repositories\InsuranceKycVerificationRepository::class);

        $this->app->bind(InsuranceServiceInterface::class, InsuranceService::class);
        $this->app->bind(\App\Services\Interfaces\KycServiceInterface::class, \App\Services\KycService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
