<?php

use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\InsurerIntegrationController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

Route::post('/insurers', [InsurerController::class, 'store']);
Route::match(['put', 'patch'], '/insurers/{insurer}', [InsurerController::class, 'update']);
Route::post('/insurer-integrations', [InsurerIntegrationController::class, 'store']);
Route::match(['put', 'patch'], '/insurer-integrations/{insurerIntegration}', [InsurerIntegrationController::class, 'update']);

Route::post('/login', [User::class, 'login']);
Route::get('/insurer-integrations/{insurerIntegration}/access-token', [InsurerIntegrationController::class, 'accessToken']);
