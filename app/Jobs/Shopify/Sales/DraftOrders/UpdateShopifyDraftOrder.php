<?php

namespace App\Jobs\Shopify\Sales\DraftOrders;

use App\InventoryVariants;
use App\LeadAttributes;
use App\Leads;
use App\Services\Shopify\ShopifyAdminAPIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateShopifyDraftOrder implements ShouldQueue
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
     * @param InventoryVariants $variants
     * @return void
     */
    public function handle(ShopifyAdminAPIService $service, InventoryVariants $variants)
    {
        $draft_attr = $this->lead->attributes()->whereName('shopifyDraftOrder')->first();
        $shop_install  = $this->lead->shop_install()->first();
        $shipping      = $this->lead->shipping_address()->first();
        $billing       = $this->lead->billing_address()->first();

        if((!is_null($draft_attr)) && (!is_null($shop_install)) && (!is_null($shop_install)) && (!is_null($shipping)) && (!is_null($billing)))
        {
            $payload = [
                'draft_order' => [
                    'id' => $draft_attr->value,
                    'line_items' => $this->prepProducts($variants),
                    'shipping_address' => $this->prepAddress($shipping),
                    'billing_address' => $this->prepAddress($billing),
                ]
            ];

            $response = $service->updateDraftOrder($shop_install, $payload);

            if($response && is_array($response) && array_key_exists('draft_order', $response))
            {
                // If success create lead_attribute record
                $draft_attr->misc = $response['draft_order'];
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
            'country_code' => $address->country,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'name' => "{$address->first_name} $address->last_name",
            'phone' => $address->phone,
            'province_code' => $address->state
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
                        'sku' => $product_variant->sku
                    ];
                }
            }
        }

        return $results;
    }
}
