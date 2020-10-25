<?php

namespace App\Projectors\Clients;

use App\Events\Orders\OrderPaymentAuthorized;
use App\Events\Orders\OrderPaymentCaptured;
use App\Jobs\Clients\Billing\ChargeClientPurchaseCommission;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class ClientActivityProjector extends Projector
{
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event)
    {
        // Determine the platform of the order.
        $platform = $event->getTransaction()->misc['platform'];

        // Use the transaction to get the client record
        $client = $event->getTransaction()->client()->first();

        ChargeClientPurchaseCommission::dispatch($client, $event->getTransaction())->onQueue('aco-'.env('APP_ENV').'-events');
    }
}
