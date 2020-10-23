<?php

namespace App\Http\Controllers\Orders;

use App\Leads;
use App\Models\Sales\Orders;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Aggregates\Orders\ShopifyOrderAggregate;

class OrdersController extends Controller
{
    protected $leads, $orders, $request;
    /**
     * Create a new controller instance.
     * @param Request $request
     * @param Leads $leads
     * @param Orders $orders
     * @return void
     */
    public function __construct(Request $request, Leads $leads, Orders $orders)
    {
        $this->leads = $leads;
        $this->orders = $orders;
        $this->request = $request;
    }

    public function createWithLead()
    {
        $results = ['success' => false, 'reason' => 'Could not produce order.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'leadUuid'  => 'bail|required|exists:leads,id',
        ]);

        if($validated->fails())
        {
            foreach($validated->errors()->toArray() as $col => $msg)
            {
                $results['reason'] = $msg[0];
                break;
            }
        }
        else
        {
            // Retrieve the lead record
            $lead = $this->leads->find($data['leadUuid']);
            $shop = session()->get('active_shop');

            // Ensure the lead is linked to the shop
            if($lead->shop_uuid == $shop->id)
            {
                // Ensure lead is ready to be converted to an order
                if($lead->isReadyToBeConvertedToOrder())
                {
                    $aggy = ShopifyOrderAggregate::retrieve($lead->id);

                    if(is_null($lead->order_uuid) || empty($lead->order_uuid))
                    {
                        // If order_uuid is null, create.
                        $payload = [
                            'reference_type' => $lead->reference_type,
                            'reference_uuid' => $lead->reference_uuid,
                            'first_name' => $lead->first_name,
                            'last_name' => $lead->last_name,
                            'email' => $lead->email,
                            'phone' => $lead->phone,
                            'shipping_uuid' => $lead->shipping_uuid,
                            'billing_uuid' => $lead->billing_uuid,
                            'lead_uuid' => $lead->id,
                            'shop_uuid' => $lead->shop_uuid,
                            'merchant_uuid' => $lead->merchant_uuid,
                            'client_uuid' => $lead->client_uuid,
                        ];

                        if($order = $this->orders->insertNew($payload))
                        {
                            // trigger event-sourcing with lead uuid to say order created.
                            $aggy = $aggy->leadIsNowOrder($order)
                                ->persist();
                        }
                    }
                    else
                    {
                        //Otherwise use.
                        //$order = $this->orders->find($lead->order_uuid);
                        $order = $aggy->getOrder();
                    }

                    if($order)
                    {
                        $shop_type = $shop->shop_type()->first()->name;
                        $results = ['success' => true, 'order' => [
                            'record' => $order->toArray(),
                            'lead' => $lead->toArray(),
                            'billing_address' => $lead->billing_address()->first()->toArray(),
                            'shipping_address' => $lead->shipping_address()->first()->toArray(),
                            'cart' => $aggy->getLineItems(),
                            'shop' => $shop,
                            'shop_type' => $shop_type,
                        ]];

                        if(strtolower($shop_type) == 'shopify')
                        {
                            $results['order']['shopify_draft_order'] = $aggy->getShopifyDraftOrder();
                            $results['order']['shopify_customer'] = $aggy->getShopifyCustomer();
                        }
                    }
                }
                else
                {
                    $results['reason'] = 'Unqualified Lead.';
                }
            }
            else
            {
                $results['reason'] = 'Invalid lead.';
            }
        }

        return response($results);
    }
}
