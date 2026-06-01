<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TataAig\TataAigMappingController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test', function () {
    return "welcome to insurance api";
});


Route::prefix('api/insurance/tata-aig')->group(function () {
    Route::get('/makes', [TataAigMappingController::class, 'getMakes']);
    Route::get('/models', [TataAigMappingController::class, 'getModels']);
    Route::get('/trims', [TataAigMappingController::class, 'getTrims']);
});
