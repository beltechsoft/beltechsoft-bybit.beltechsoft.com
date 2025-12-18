<?php

use Illuminate\Support\Facades\Route;
use Modules\ByBitData\Http\Controllers\TradesHistoryController;

Route::middleware(['web'])->group(function () {
    Route::resource('trades-history', TradesHistoryController::class)->names('trades-history');
});


