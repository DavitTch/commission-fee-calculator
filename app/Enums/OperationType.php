<?php

namespace App\Enums;

enum OperationType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
}
