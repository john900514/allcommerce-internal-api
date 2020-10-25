<?php

namespace App\Jobs\Clients\Billing;

use App\Aggregates\Clients\ClientBillableActivity;
use App\Clients;
use App\Models\Sales\Transactions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ChargeClientPurchaseCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client, $transaction;
    /**
     * Create a new job instance.
     * @param Clients $client
     * @param Transactions $transaction
     * @return void
     */
    public function __construct(Clients $client, Transactions $transaction)
    {
        $this->client = $client;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Call upon $aggy the ClientBillableActivity aggregate.
        $aggy = ClientBillableActivity::retrieve($this->client->id);

        if(is_null($aggy->getClient()))
        {
            $aggy = $aggy->setClient($this->client);
        }

        $aggy->setActiveMonth()
            ->setNewTransaction($this->transaction)
            ->persist();
    }
}
