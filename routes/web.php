<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\vehicleMapping\VehicleMappingController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test', function () {
    return "welcome to insurance api";
});


Route::prefix('api/insurance')->group(function () {
    Route::get('/makes', [VehicleMappingController::class, 'getMakes']);
    Route::get('/models', [VehicleMappingController::class, 'getModels']);
    Route::get('/trims', [VehicleMappingController::class, 'getTrims']);
});
