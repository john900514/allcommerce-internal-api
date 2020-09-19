<?php

namespace App\Jobs\Leads;

use App\Phones;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateOrUpdatePhoneAddressRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone, $deets;

    public function __construct($phone, $deets)
    {
        $this->phone = $phone;
        $this->deets = $deets;
    }

    public function handle(Phones $phones_model)
    {

    }
}
