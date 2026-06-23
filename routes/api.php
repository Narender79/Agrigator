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

Route::post('/login', [User::class, 'login']);
Route::post('/google-login', [User::class, 'login']);

Route::post('/users', [ApiUserController::class, 'store']);
Route::match(['put', 'patch'], '/users/{user}', [ApiUserController::class, 'update']);
Route::get('/users/{user}', [ApiUserController::class, 'show']);

Route::post('/partners', 'App\\Http\\Controllers\\Api\\PartnerController@store');
Route::match(['put', 'patch'], '/partners/{partner}', 'App\\Http\\Controllers\\Api\\PartnerController@update');
Route::get('/partners/user/{userId}', 'App\\Http\\Controllers\\Api\\PartnerController@showByUserId');

Route::post('/partner-credentials', [PartnerCredentialController::class, 'store']);
Route::get('/partner-credentials/partner/{partnerId}', [PartnerCredentialController::class, 'showByPartnerId']);


Route::post('/insurers', [InsurerController::class, 'store']);
Route::match(['put', 'patch'], '/insurers/{insurer}', [InsurerController::class, 'update']);
Route::get('/insurers/user/{userId}', [InsurerController::class, 'showByUserId']);

Route::post('/insurer-integrations', [InsurerIntegrationController::class, 'store']);
Route::match(['put', 'patch'], '/insurer-integrations/{insurerIntegration}', [InsurerIntegrationController::class, 'update']);

Route::get('/insurer-integrations/{insurerIntegration}/access-token', [InsurerIntegrationController::class, 'accessToken']);
Route::get('/insurer-integrations/insurer/{insurerId}', [InsurerIntegrationController::class, 'showByInsurerId']);

Route::get('/vehicle-details', [\App\Http\Controllers\Api\VehicleDetailsController::class, 'getDetails']);

Route::middleware('merchant.api')->group(function () {
    Route::post('/insurance/getQuotes', [QuoteController::class, 'store']);
    Route::get('/insurance/makes', [VehicleMappingController::class, 'getMakes']);
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
});

Route::post('/login', [User::class, 'login']);

Route::get('/rto', [RTO::class, 'getRtoData']);
