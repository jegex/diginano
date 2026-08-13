<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Manual = 'manual';
    case Midtrans = 'midtrans';
    case Cryptomus = 'cryptomus';
}
