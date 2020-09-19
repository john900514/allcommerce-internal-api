<?php

namespace App\Jobs\Leads;

use App\BillingAddresses;
use App\Leads;
use App\ShippingAddresses;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LinkEmailToBillShip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shipping, $billing, $lead;
    /**
     * Create a new event instance.
     * @param ShippingAddresses $shipping
     * @param BillingAddresses $billing
     * @param Leads $lead
     * @return void
     */
    public function __construct(ShippingAddresses $shipping, BillingAddresses $billing, Leads $lead)
    {
        $this->shipping = $shipping;
        $this->billing = $billing;
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->shipping->email = $this->lead->email;
        $this->shipping->save();

        $this->billing->email = $this->lead->email;
        $this->billing->save();
    }
}
