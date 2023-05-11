<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public function getRates()
    {
        $result = Http::get(config('exchange-rates.url'))->json();

        return $result['rates'];
    }

    public function convertToEur(float $amount, float $exchangeRate): float
    {
        return $amount * (1 / $exchangeRate);
    }

    public function convertFromEur(float $amount, float $exchangeRate): float
    {
        return $amount * $exchangeRate;
    }
}
