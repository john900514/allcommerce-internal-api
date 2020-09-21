<?php

namespace App\Jobs\Shopify\Sales\DraftOrders;

use App\BillingAddresses;
use App\InventoryVariants;
use App\Leads;
use App\LeadAttributes;
use App\ShippingAddresses;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Shopify\ShopifyAdminAPIService;

class CreateShopifyDraftOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lead, $checkout_details;

    /**
     * Create a new event instance.
     * @param Leads $lead
     * @param array $checkout_details
     * @return void
     */
    public function __construct(Leads $lead, array $checkout_details)
    {
        $this->lead = $lead;
        $this->checkout_details = $checkout_details;
    }

    /**
     * Execute the job.
     * @param ShopifyAdminAPIService $service
     * @param LeadAttributes $attributes
     * @return void
     */
    public function handle(ShopifyAdminAPIService $service, LeadAttributes $attributes, InventoryVariants $variants)
    {
        $customer_attr = $this->lead->attributes()->whereName('shopifyCustomer')->first();
        $shop_install  = $this->lead->shop_install()->first();
        $shipping      = $this->lead->shipping_address()->first();
        $billing       = $this->lead->billing_address()->first();

        if((!is_null($customer_attr)) && (!is_null($shop_install)) && (!is_null($shipping)) && (!is_null($billing)))
        {
            $payload = [
                'draft_order' => [
                    'line_items' => $this->prepProducts($variants),
                    'shipping_address' => $this->prepAddress($shipping),
                    'billing_address' => $this->prepAddress($shipping),
                    'customer' => [
                        'id' => intVal($customer_attr->value)
                    ]
                ]
            ];

            $response = $service->postDraftOrder($shop_install, $payload);

            if($response && is_array($response) && array_key_exists('draft_order', $response))
            {
                $draft_order = $response['draft_order'];

                // If success create lead_attribute record
                $draft_attr = $this->lead->attributes()
                    ->whereName('shopifyDraftOrder')
                    ->first();

                if(is_null($draft_attr))
                {
                    $draft_attr = new $attributes;
                }

                if(!is_null($draft_attr))
                {
                    $draft_attr->lead_uuid = $this->lead->id;
                    $draft_attr->name = 'shopifyDraftOrder';
                    $draft_attr->value = $draft_order['id'];
                    $draft_attr->misc = $draft_order;
                    $draft_attr->active = 1;
                    $draft_attr->shop_uuid = $this->lead->shop_uuid;
                    $draft_attr->merchant_uuid = $this->lead->merchant_uuid;
                    $draft_attr->client_uuid = $this->lead->client_uuid;
                }

                $draft_attr->save();
            }
        }
    }

    private function prepAddress($address)
    {
        $results = [
            'address1' => $address->address,
            'address2' => (!is_null($address->address2)) ? $address->address2 : ((!is_null($address->apt)) ? $address->apt : ''),
            'city' => $address->city,
            'country' => strtoupper($address->country),
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'name' => "{$address->first_name} $address->last_name",
            'phone' => $address->phone,
            'province' => $address->state,
            'zip' => $address->zip
        ];

        return $results;
    }

    private function prepProducts(InventoryVariants $variants)
    {
        $results = [];

        if(count($this->checkout_details['products']) > 0)
        {
            foreach ($this->checkout_details['products'] as $line_item)
            {
                $product_variant = $variants->find($line_item['variant']);

                if(!is_null($product_variant))
                {
                    $results[] = [
                        'variant_id' => $product_variant->inventory_item_id,
                        'product_id' => $product_variant->inventory_id,
                        'quantity' => $line_item['qty'],
                        'sku' => $product_variant->sku,
                        'taxable' => true
                    ];
                }
            }
        }

        return $results;
    }
}
