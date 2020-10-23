<?php

namespace App\Services\PaymentGateways\CreditCard;

interface CreditCardGateway
{
    public function authorize(array $details);

    public function capture();
}
