<?php

use Illuminate\Support\Facades\Route;
use Modules\ByBitData\Http\Controllers\ByBitDataController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bybitdatas', ByBitDataController::class)->names('bybitdata');
});
