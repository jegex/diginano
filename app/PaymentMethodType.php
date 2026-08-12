<?php

namespace App;

enum PaymentMethodType: string
{
    case Manual = 'manual';
    case Midtrans = 'midtrans';
    case Cryptomus = 'cryptomus';
}
