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
        $default_item = $inventory->getShopDefaultItem($this->active_install->uuid);
        $variant = $default_item->variants()->first();
        $options = $default_item->variant_options()->get();
        $option_ids = [];
        foreach($options as $option)
        {
            $option_ids[] = $option->uuid;
        }

        // Use the data to generate the Checkout Funnel Record
        $funnel_payload = [
            'shop_install_id' => $this->active_install->uuid,
            'funnel_name'     => 'Baby\'s 1st Checkout Funnel',
            'shop_platform'   => 'shopify',
            'active'          => 1
        ];

        $funnel = $funnels->insert($funnel_payload);

        // Use the data to generate the checkout funnel attribute records
        $attr_payload = [
            [
                'funnel_uuid' => $funnel->uuid,
                'funnel_attribute' => 'item-1',
                'funnel_value' => $default_item->uuid,
                'funnel_misc_json' => [
                    'qty' => 1,
                    'variant' => $variant->uuid,
                    'options'  => $option_ids
                ],
                'active' => 1
            ]
        ];

        foreach ($attr_payload as $payload)
        {
            $attributes->insert($payload);
        }

        activity()
            ->causedBy($this->active_install)
            ->performedOn($funnel)
            ->withProperties([$funnel_payload, $attr_payload])
            ->log('Set up a merchant\'s first Checkout Funnel');
    }
}
