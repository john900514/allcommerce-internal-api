<?php

namespace App\Jobs\CheckoutFunnels;

use App\CheckoutFunnels;
use App\ShopifyInstalls;
use App\MerchantInventory;
use Illuminate\Bus\Queueable;
use App\CheckoutFunnelAttributes;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateFirstCheckoutFunnel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $active_install;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ShopifyInstalls $install)
    {
        $this->active_install = $install;
    }

    public function handle(CheckoutFunnels $funnels,
                           MerchantInventory $inventory,
                           CheckoutFunnelAttributes $attributes)
    {
        // Use the ShopifyInstallId to locate the shop's default item
        $default_item = $inventory->getShopDefaultItem($this->active_install->id);
        $variant = $default_item->variants()->first();
        $options = $default_item->variant_options()->get();
        $option_ids = [];
        foreach($options as $option)
        {
            $option_ids[] = $option->uuid;
        }

        // Use the data to generate the Checkout Funnel Record
        $funnel_payload = [
            'shop_id' => $this->active_install->shop_uuid,
            'shop_install_id' => $this->active_install->id,
            'funnel_name'     => 'Baby\'s 1st Checkout Funnel',
            'shop_platform'   => 'shopify',
            'default'         => 1,
            'active'          => 1
        ];

        $funnel = $funnels->insert($funnel_payload);

        Log::info($funnel->toArray());
        // Use the data to generate the checkout funnel attribute records
        $attr_payload = [
            [
                'funnel_uuid' => $funnel->id,
                'funnel_attribute' => 'item-1',
                'funnel_value' => $default_item->id,
                'funnel_misc_json' => [
                    'qty' => 1,
                    'variant' => $variant->id,
                    'options'  => $option_ids
                ],
                'active' => 1
            ],
            [
                'funnel_uuid' => $funnel->id,
                'funnel_attribute' => 'blade-template',
                'funnel_value' => 'checkouts.default.experience',
                'funnel_misc_json' => [],
                'active' => 1
            ]
        ];

        foreach ($attr_payload as $payload)
        {
            $attributes->insert($payload);
        }

        /*
        activity()
            ->causedBy($this->active_install)
            ->performedOn($funnel)
            ->withProperties([$funnel_payload, $attr_payload])
            ->log('Set up a merchant\'s first Checkout Funnel');
        */
    }
}
