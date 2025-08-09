<?php

if (!function_exists('getBankOptions')) {
    function getBankOptions() {
        return \App\Helpers\BankHelper::getBankOptions();
    }
}

if (!function_exists('getPopularBankOptions')) {
    function getPopularBankOptions() {
        return \App\Helpers\BankHelper::getPopularBankOptions();
    }
}

if (!function_exists('getBankCode')) {
    function getBankCode($bankName) {
        return \App\Helpers\BankHelper::getBankCode($bankName);
    }
}

if (!function_exists('getShortBankName')) {
    function getShortBankName($bankName) {
        return \App\Helpers\BankHelper::getShortBankName($bankName);
    }
}
