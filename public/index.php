<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

if (!function_exists('priceFormat')) {
    function priceFormat($number) {
        if ($number >= 1_000_000) {
            return number_format($number / 1_000_000, 2, ',', '') . 'М';
        } elseif ($number >= 1_000) {
            return number_format($number / 1_000, 2, ',', '') . 'К';
        } else {
            return number_format($number, 2, ',', '');
        }
    }
}
function volumePercentDiff($buy, $sell) {
    if ($buy == 0 && $sell == 0) {
        return '0%';
    } elseif ($buy == 0) {
        return '+∞%';
    }

    $percent = abs($sell - $buy) / $buy * 100; // abs делает всегда положительным
    return number_format($percent, 2, ',', '') . '%';
}

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
