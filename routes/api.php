<?php

use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\InsurerIntegrationController;
use App\Http\Controllers\Api\PartnerCredentialController;
use App\Http\Controllers\User;
use App\Http\Controllers\Api\UserController as ApiUserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [User::class, 'login']);

Route::post('/users', [ApiUserController::class, 'store']);
Route::match(['put', 'patch'], '/users/{user}', [ApiUserController::class, 'update']);
Route::post('/partner-credentials', [PartnerCredentialController::class, 'store']);


Route::post('/insurers', [InsurerController::class, 'store']);
Route::match(['put', 'patch'], '/insurers/{insurer}', [InsurerController::class, 'update']);
Route::post('/insurer-integrations', [InsurerIntegrationController::class, 'store']);
Route::match(['put', 'patch'], '/insurer-integrations/{insurerIntegration}', [InsurerIntegrationController::class, 'update']);
Route::get('/insurer-integrations/{insurerIntegration}/access-token', [InsurerIntegrationController::class, 'accessToken']);
Route::middleware('merchant.api')->group(function () {
	
});
