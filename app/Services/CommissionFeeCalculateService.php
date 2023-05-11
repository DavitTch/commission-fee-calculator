<?php

namespace App\Services;

use App\Enums\ClientType;
use App\Enums\Currency;
use App\Enums\OperationType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CommissionFeeCalculateService
{
    private $depositFee;
    private $privateWithdrawRate;
    private $businessWithdrawRate;
    private $privateWithdrawFreeLimit;
    private $privateWithdrawFreeLimitCount;
    private $exchangeRateService;
    private $exchangeRates;
    private $withdraws;

    public function __construct(private string $filePath)
    {
        $this->exchangeRateService = new ExchangeRateService();
        $this->exchangeRates = $this->exchangeRateService->getRates();
        $this->depositFee = config('main.deposit_fee');
        $this->privateWithdrawRate = config('main.private_withdraw_rate');
        $this->businessWithdrawRate = config('main.business_withdraw_rate');
        $this->privateWithdrawFreeLimit = config('main.private_withdraw_free_limit');
        $this->privateWithdrawFreeLimitCount = config('main.private_withdraw_free_limit_count');
    }

    /**
     * @throws \Exception
     */
    public function calculate()
    {
        $operations = $this->getData();
        $results = [];

        foreach ($operations as $operation) {
            $clientId = $operation->client_id;
            $amount = $operation->amount;
            $fee = 0;
            $startOfWeek = Carbon::parse($operation->date)->startOfWeek()->toDateString();

            if (!isset($this->withdraws[$clientId]['weekOperations'][$startOfWeek])) {
                $this->withdraws[$clientId]['weekOperations'][$startOfWeek] = [
                    'operationCount' => 0,
                    'amount' => 0
                ];
            }

            $clientWeeklyWithdraws = $this->withdraws[$clientId]['weekOperations'][$startOfWeek];

            // Convert operation amount to EUR if needed
            $operation->amount_in_eur = $operation->currency !== Currency::EUR->value
                ? $this->exchangeRateService->convertToEur($amount, $this->exchangeRates[$operation->currency])
                : $amount;

            if ($operation->type === OperationType::DEPOSIT->value) {
                $fee = $this->calculateDepositFee($operation);
            } elseif ($operation->type === OperationType::WITHDRAW->value) {
                $fee = $this->calculateWithdrawalFee($operation, $clientWeeklyWithdraws, $startOfWeek);
            }

            $results[] = $fee;
        }

        return $results;
    }

    private function calculateDepositFee($operation)
    {
        return $this->roundUp($operation->amount * $this->depositFee / 100);
    }

    /**
     * @throws \Exception
     */
    private function calculateWithdrawalFee($operation, $clientWeeklyWithdraws, $startOfWeek)
    {
        $fee = 0;

        if ($operation->client_type === ClientType::PRIVATE->value) {
            $amountLeft = $this->privateWithdrawFreeLimit - $clientWeeklyWithdraws['amount'];
            if (($amountLeft > 0) && ($clientWeeklyWithdraws['operationCount'] < $this->privateWithdrawFreeLimitCount)) {
                if ($operation->amount_in_eur > $amountLeft) {
                    $excessAmount = $operation->amount_in_eur - $amountLeft;
                    $fee = $this->exchangeRateService->convertFromEur(
                            $excessAmount,
                            $this->exchangeRates[$operation->currency]
                        ) * $this->privateWithdrawRate;
                }
            } else {
                $fee = $operation->amount * $this->privateWithdrawRate;
            }

            $clientWeeklyWithdraws['operationCount']++;
            $clientWeeklyWithdraws['amount'] += $operation->amount_in_eur;
        } elseif ($operation->client_type === ClientType::BUSINESS->value) {
            $fee = $operation->amount * $this->businessWithdrawRate;
        } else {
            throw new \Exception('Incorrect operation type!');
        }

        $this->withdraws[$operation->client_id]['weekOperations'][$startOfWeek] = $clientWeeklyWithdraws;

        return $this->roundUp($fee);
    }

    private function roundUp($fee)
    {
        return ceil($fee * 100) / 100;
    }

    private function getData(): Collection
    {
        $header = [
            'date',
            'client_id',
            'client_type',
            'type',
            'amount',
            'currency',
        ];

        $operationsRawData = array_map('str_getcsv', file($this->filePath));

        $operations = collect($operationsRawData)->map(
            function ($row) use ($header) {
                return (object)array_combine($header, $row);
            }
        );

        return collect($operations);
    }
}
