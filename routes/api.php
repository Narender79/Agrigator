<?php

use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\InsurerIntegrationController;
use Illuminate\Support\Facades\Route;

Route::post('/insurers', [InsurerController::class, 'store']);
Route::post('/insurer-integrations', [InsurerIntegrationController::class, 'store']);