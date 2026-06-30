<?php

use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\InsurerIntegrationController;
use App\Http\Controllers\Api\PartnerCredentialController;
use App\Http\Controllers\Api\RTO;
use App\Http\Controllers\User;
use App\Http\Controllers\Api\UserController as ApiUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\vehicleMapping\VehicleMappingController;
use App\Http\Controllers\Api\ProposalController;
use \App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PaymentController;

Route::post('/login', [User::class, 'login']);
Route::post('/google-login', [User::class, 'login']);

Route::post('/users', [ApiUserController::class, 'store']);
Route::match(['put', 'patch'], '/users/{user}', [ApiUserController::class, 'update']);
Route::get('/users/{user}', [ApiUserController::class, 'show']);

Route::post('/partners', [PartnerController::class, 'store']);
Route::match(['put', 'patch'], '/partners/{id}', [PartnerController::class, 'update']);
Route::get('/partners/user/{userId}', [PartnerController::class, 'showByUserId']);

Route::post('/partner-credentials', [PartnerCredentialController::class, 'store']);
Route::get('/partner-credentials/partner/{partnerId}', [PartnerCredentialController::class, 'showByPartnerId']);


Route::post('/insurers', [InsurerController::class, 'store']);
Route::match(['put', 'patch'], '/insurers/{id}', [InsurerController::class, 'update']);
Route::get('/insurers/user/{userId}', [InsurerController::class, 'showByUserId']);

Route::post('/insurer-integrations', [InsurerIntegrationController::class, 'store']);
Route::match(['put', 'patch'], '/insurer-integrations/{insurerIntegration}', [InsurerIntegrationController::class, 'update']);

Route::get('/insurer-integrations/{insurerIntegration}/access-token', [InsurerIntegrationController::class, 'accessToken']);
Route::get('/insurer-integrations/insurer/{insurerId}', [InsurerIntegrationController::class, 'showByInsurerId']);

Route::get('/vehicle-details', [\App\Http\Controllers\Api\VehicleDetailsController::class, 'getDetails']);

Route::get('/insurance/available-options', [QuoteController::class, 'availableOptions']);

Route::middleware('merchant.api')->group(function () {
    Route::post('/insurance/getQuotes', [QuoteController::class, 'store']);
    Route::get('/insurance/2w/makes', [VehicleMappingController::class, 'getMakes2w']);
    Route::get('/insurance/4w/makes', [VehicleMappingController::class, 'getMakes4w']);
    Route::get('/insurance/models', [VehicleMappingController::class, 'getModels']);
    Route::get('/insurance/trims', [VehicleMappingController::class, 'getTrims']);
    Route::post('/insurance/proposal', [ProposalController::class, 'store']);
    Route::post('/insurance/save-quote-payment', [QuoteController::class, 'saveQuotePayment']);
    Route::post('/insurance/kyc/verify', [KycController::class, 'verify']);
    Route::prefix('insurance')->group(function () {
        Route::controller(InsuranceController::class)->group(function () {
            Route::get("get-quotes/{quoteId}", 'getQuotesByQuoteId');
        });
    });
    Route::post('/insurance/verify-insurer-payment', [PaymentController::class, 'verifyInsurerPayment']);
    Route::post('/insurance/policy/download', [PaymentController::class, 'downloadPolicy']);
});

Route::post('/login', [User::class, 'login']);

Route::get('/rto', [RTO::class, 'getRtoData']);
