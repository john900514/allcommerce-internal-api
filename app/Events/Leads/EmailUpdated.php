<?php

namespace App\Events\Leads;

use App\Emails;
use App\Leads;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class EmailUpdated extends ShouldBeStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $email, $emails, $lead;
    /**
     * Create a new event instance.
     * @param string $email
     * @param Emails $emails
     * @param Leads $lead
     * @return void
     */
    public function __construct(string $email, Emails $emails, Leads $lead)
    {
        $this->email = $email;
        $this->emails = $emails;
        $this->lead = $lead;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getEmails()
    {
        return $this->emails;
    }

    public function getLead()
    {
        return $this->lead;
    }
}
