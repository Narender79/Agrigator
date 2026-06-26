<?php

namespace App\Providers;

use App\Repositories\InsuranceKycVerificationRepository;
use App\Repositories\InsurerRepository;
use App\Repositories\Interfaces\InsuranceKycVerificationRepositoryInterface;
use App\Repositories\Interfaces\InsurerRepositoryInterface;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use App\Repositories\Interfaces\ProposalRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\VehicleMappingRepositoryInterface;
use App\Repositories\VehicleMappingRepository;
use App\Repositories\Interfaces\QuoteRepositoryInterface;
use App\Repositories\Interfaces\UsersInsuranceRepositoryInterface;
use App\Repositories\PartnerRepository;
use App\Repositories\ProposalRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\UsersInsuranceRepository;
use App\Services\InsuranceService;
use App\Services\InsurerService;
use App\Services\Interfaces\InsuranceServiceInterface;
use App\Services\Interfaces\InsurerServiceInterface;
use App\Services\Interfaces\KycServiceInterface;
use App\Services\Interfaces\PartnerServiceInterface;
use App\Services\Interfaces\PaymentServiceInterface;
use App\Services\Interfaces\ProposalServiceInterface;
use App\Services\Interfaces\QuoteServiceInterface;
use App\Services\KycService;
use App\Services\PartnerService;
use App\Services\PaymentService;
use App\Services\ProposalService;
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

        // Bind Quote interface & repository
        $this->app->bind(QuoteRepositoryInterface::class, QuoteRepository::class);
        $this->app->bind(QuoteServiceInterface::class, QuoteService::class);

        // Bind Proposal interface & repository
        $this->app->bind(ProposalRepositoryInterface::class, ProposalRepository::class);
        $this->app->bind(ProposalServiceInterface::class, ProposalService::class);

        // Binding Insurer interface and repository
        $this->app->bind(InsurerRepositoryInterface::class, InsurerRepository::class);
        $this->app->bind(UsersInsuranceRepositoryInterface::class, UsersInsuranceRepository::class);

        $this->app->bind(InsuranceKycVerificationRepositoryInterface::class, InsuranceKycVerificationRepository::class);
        $this->app->bind(KycServiceInterface::class, KycService::class);

        $this->app->bind(InsuranceServiceInterface::class, InsuranceService::class);

        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
        $this->app->bind(InsurerServiceInterface::class, InsurerService::class);
        $this->app->bind(PartnerServiceInterface::class, PartnerService::class);
        $this->app->bind(PartnerRepositoryInterface::class, PartnerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
