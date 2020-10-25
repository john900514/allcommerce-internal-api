<?php

namespace App\Services\PaymentGateways\CreditCard;

use Ramsey\Uuid\Uuid;

class DryRunGateway implements CreditCardGateway
{
    protected $client_enabled_uuid;

    public function __construct($client_enabled_uuid)
    {
        $this->client_enabled_uuid = $client_enabled_uuid;

        // Because this is dry run we don't need any credentials;
    }

    public function authorize(array $details)
    {
        $results = ['success' => false, 'reason' => 'Declined'];

        if(array_key_exists('cc', $details))
        {
            switch($details['cc'])
            {
                case '4111111111111111':
                    $results['reason'] = 'Denied. Fail No.';
                    break;

                default:
                    // @todo - maybe some validation of the ccExpy in the past
                    $results = ['success' => true, 'authorization' => [
                        'status' => 'authorized',
                        'price'  => $details['price'],
                        'date'   => date('Y-m-d h:m:s'),
                        'auth_id'=> Uuid::uuid4()->toString(),
                        'capture_token' => Uuid::uuid4()->toString()
                    ]];
            }
        }

        return $results;
    }

    public function capture(array $details)
    {
        $results = ['success' => false, 'reason' => 'Declined'];

        if(array_key_exists('capture_token', $details))
        {
            $results = ['success' => true, 'sale' => [
                'status' => 'captured',
                'price'  => $details['price'],
                'date'   => date('Y-m-d h:m:s'),
                'auth_id'=> $details['auth_id'],
                'sale_id' => Uuid::uuid4()->toString()
            ]];
        }

        return $results;
    }
}
