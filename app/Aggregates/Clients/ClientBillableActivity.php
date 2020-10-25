<?php

namespace App\Aggregates\Clients;

use App\Clients;
use App\Events\Clients\ClientBillingMonthUpdated;
use App\Events\Clients\ClientBillingStarted;
use App\Events\Clients\CutNewCommission;
use App\Models\Sales\Transactions;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class ClientBillableActivity extends AggregateRoot
{
    protected $client;
    protected $months = [];
    protected $billable_transactions = [];
    protected $sms_triggers = [];
    protected $active_month;
    protected $total_commission = 0;
    protected $active_month_commission = 0;
    protected $total_revenue = 0;
    protected $active_month_revenue = 0;

    public function applyClientBillingStarted(ClientBillingStarted $event) {
        $this->client = $event->getClient();

        $monthYear = date('m/Y', strtotime('now'));
        $this->active_month = $monthYear;
        $this->months[$monthYear] = [];
        $this->billable_transactions[$monthYear] = [];
        $this->sms_triggers[$monthYear] = [];
    }

    public function applyClientBillingMonthUpdated(ClientBillingMonthUpdated $event) {
        $this->active_month = $event->getMonth();

        if(!array_key_exists($this->active_month, $this->months))
        {
            $this->months[$this->active_month] = [];
        }

        if(!array_key_exists($this->active_month, $this->billable_transactions))
        {
            $this->billable_transactions[$this->active_month] = [];
        }

        if(!array_key_exists($this->active_month, $this->sms_triggers))
        {
            $this->sms_triggers[$this->active_month] = [];
        }

        $this->active_month_commission = 0;
        $this->active_month_revenue = 0;
    }

    public function applyCutNewCommission(CutNewCommission $event)
    {
        $this->billable_transactions[$this->active_month][] = $event->getTransaction();
        $amount = $event->getTransaction()->total;
        $commission = $event->getTransaction()->commission_amount;

        $this->total_revenue += $amount;
        $this->active_month_revenue += $amount;

        $this->total_commission += $commission;
        $this->active_month_commission += $commission;
    }

    public function apply() {}

    public function setClient(Clients $client)
    {
        $this->client = $client;
        $this->recordThat(new ClientBillingStarted($client));

        return $this;
    }

    public function setActiveMonth()
    {
        $active_month = date('m/Y', strtotime('now'));

        if($this->active_month != $active_month)
        {
            $this->active_month = $active_month;
            $this->recordThat(new ClientBillingMonthUpdated($active_month));
        }

        return $this;
    }

    public function setNewTransaction(Transactions $transaction)
    {
        $this->recordThat(new CutNewCommission($transaction));
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }
}
