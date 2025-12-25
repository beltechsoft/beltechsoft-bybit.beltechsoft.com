<?php
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
