<?php

use Illuminate\Support\Facades\Route;
use Modules\ByBitData\Http\Controllers\ByBitDataController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('bybitdatas', ByBitDataController::class)->names('bybitdata');
});
