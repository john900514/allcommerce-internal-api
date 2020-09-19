<?php

namespace App\Jobs\Leads;

use App\Emails;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateOrUpdateEmailAddressRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email, $deets;

    public function __construct($email, $deets)
    {
        $this->email = $email;
        $this->deets = $deets;
    }

    public function handle(Emails $emails_model)
    {

    }
}
