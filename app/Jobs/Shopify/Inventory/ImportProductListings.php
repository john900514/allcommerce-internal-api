<?php

namespace App\Jobs\Shopify\Inventory;

use App\CheckoutFunnels;
use App\InventoryImages;
use App\InventoryVariants;
use App\Jobs\CheckoutFunnels\GenerateFirstCheckoutFunnel;
use App\MerchantInventory;
use App\ShopifyInstalls;
use App\VariantsOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ixudra\Curl\Facades\Curl;

class ImportProductListings implements ShouldQueue
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MerchantInventory $inventory,
                           CheckoutFunnels $funnels,
                           InventoryVariants $inv_variants,
                           InventoryImages $images,
                           VariantsOptions $options)
    {
        $local_items = $inventory->getAllItemsByShopId($this->active_install->id, 'shopify');

        $headers = [
            'X-Shopify-Access-Token: '.$this->active_install->access_token
        ];

        // Call out to shopify for product listing or fail
        //$response  = Curl::to('https://'.$this->active_install->shopify_store_url.'/admin/api/2020-07/product_listings.json')
        $response  = Curl::to('https://'.$this->active_install->shopify_store_url.'/admin/api/2020-07/products.json')
            ->withHeaders($headers)
            ->asJson(true)
            ->get();

        if(is_array($response) && array_key_exists('products', $response))
        {
            if(count($response['products']) > 0)
            {
                // if items, for each listing
                foreach($response['products'] as $idx => $listing)
                {
                    if(is_null($local_listing = $local_items->where('platform_id', '=', $listing['id'])->first()))
                    {
                        // store it
                        $local_listing = $this->createNewListing($listing, $local_items, $idx, $inventory);
                    }
                    else
                    {
                        // update the product's data
                        $local_listing = $this->updateListing($listing, $local_listing, $idx);
                    }

                    if($local_listing)
                    {
                        //store its variants
                        $this->createOrUpdateVariants($listing['variants'], $inv_variants, $local_listing);

                        // store its images
                        $this->createOrUpdateImages($listing['images'], $images, $local_listing);

                        // store its options
                        $this->createOrUpdateOptions($listing['options'], $options, $local_listing);
                    }
                }

                // once completed, eval checkout_funnels for any active funnels (possibly from a previous install)
                $checkout_funnels = $funnels->getAllActiveFunnels('shopify', $this->active_install->uuid);

                if(count($checkout_funnels) == 0)
                {
                    // if there are no active funnels for this merchant, queue the generateFirstFunnel job
                    GenerateFirstCheckoutFunnel::dispatch($this->active_install)->onQueue('aco-'.env('APP_ENV').'-funnels');
                }

                // activity log the success
                activity('import-product-listing-success')
                    ->causedBy($this->active_install)
                    ->withProperties([$response])
                    ->log('Shopify Inventory imported!');
            }
            else
            {
                // If no items, logs success and quit
                activity('import-product-listing-success')
                    ->causedBy($this->active_install)
                    ->withProperties([$response])
                    ->log('Merchant has no available Shopify Inventory.');
            }
        }
        else
        {
            activity('import-product-listing-fail')
                ->causedBy($this->active_install)
                ->withProperties([$response])
                ->log('Could not retrieve Shopify Inventory');
        }
    }

    private function updateListing($listing, MerchantInventory $local_listing, $idx)
    {
        $args = [
            'shop_id' => $this->active_install->shop_uuid,
            'shop_install_id' => $this->active_install->id,
            'platform_id' => $listing['id'],
            'platform' => 'shopify',
            'title' => $listing['title'],
            'body_html' => $listing['body_html'],
            'vendor' => $listing['vendor'],
            'product_type' => $listing['product_type'],
            'handle' => $listing['handle'],
            'published_at' => $listing['published_at'],
            'tags' => $listing['tags'],
        ];

        foreach($args as $col => $val)
        {
            $local_listing->$col = $val;
        }

        $local_listing->save();

        return $local_listing;
    }

    private function createNewListing($listing, $local_items, $idx, MerchantInventory $inventory)
    {
        $args = [
            'shop_id' => $this->active_install->shop_uuid,
            'shop_install_id' => $this->active_install->id,
            'platform_id' => $listing['id'],
            'platform' => 'shopify',
            'title' => $listing['title'],
            'body_html' => $listing['body_html'],
            'vendor' => $listing['vendor'],
            'product_type' => $listing['product_type'],
            'handle' => $listing['handle'],
            'published_at' => $listing['published_at'],
            'tags' => $listing['tags'],
        ];

        if(count($local_items) == 0)
        {
            $args['active'] = ($idx == 0) ? 1 : 0;
            $args['default_item'] = ($idx == 0) ? 1 : 0;
        }

        return $inventory->insert($args);
    }

    private function createOrUpdateVariants($variants, InventoryVariants $inv_variants, MerchantInventory $local_listing)
    {
        foreach($variants as $v => $variant)
        {
            $local_variant = $inv_variants->whereInventoryItemId($variant['product_id'])
                ->whereInventoryId($local_listing->id)
                ->first();

            $option = [
                'option1' => $variant['option1'],
                'option2' => $variant['option2'],
                'option3' => $variant['option3'],
            ];

            $args = [
                'shop_id' => $this->active_install->shop_uuid,
                'inventory_id' => $variant['product_id'],
                'inventory_item_id' => $variant['id'],
                'title' => $variant['title'],
                'price' => $variant['price'],
                'sku' => $variant['sku'],
                'position' => $variant['position'],
                'inventory_policy' => $variant['inventory_policy'],
                'compare_at_price' => $variant['compare_at_price'],
                'fulfillment_service' => $variant['fulfillment_service'],
                'inventory_management' => $variant['inventory_management'],
                'taxable' => $variant['taxable'],
                'barcode' => $variant['barcode'],
                'grams' => $variant['grams'],
                'image_id' => $variant['image_id'],
                'weight' => $variant['weight'],
                'weight_unit' => $variant['weight_unit'],
                'inventory_quantity' => $variant['inventory_quantity'],
                'requires_shipping' => $variant['requires_shipping'],
                'old_inventory_quantity' => 0,
                'options' => $option,
            ];

            // check for the record in the db
            if(is_null($local_variant))
            {
                $local_variant = $inv_variants->insert($args);
            }
            else
            {
                foreach($args as $col => $val)
                {
                    $local_variant->$col = $val;
                }

                $local_variant->save();
            }
        }
    }

    private function createOrUpdateImages($images, InventoryImages $inv_images, MerchantInventory $local_listing)
    {
        foreach($images as $i => $image)
        {
            $args = [
                'shop_id' => $this->active_install->shop_uuid,
                'inventory_uuid' => $local_listing->id,
                'platform_id' => $local_listing->platform_id,
                'inventory_platform_id' => $image['id'],
                'position' => $image['position'],
                'width' => $image['width'],
                'height' => $image['height'],
                'src' => $image['src'],
                'variant_ids' => $image['variant_ids'],
            ];

            $local_image = $inv_images->whereInventoryUuid($local_listing->id)
                ->whereInventoryPlatformId($image['id'])
                ->first();

            if(is_null($local_image))
            {
                $local_image = $inv_images->insert($args);
            }
            else
            {
                foreach($args as $col => $val)
                {
                    $local_image->$col = $val;
                }

                $local_image->save();
            }
        }
    }

    private function createOrUpdateOptions($options, VariantsOptions $inv_options, MerchantInventory $local_listing)
    {
        foreach($options as $o => $option)
        {
            $args = [
                'shop_id' => $local_listing->shop_id,
                'inventory_id' => $local_listing->id,
                'platform_id' => $local_listing->platform_id,
                'inventory_platform_id' => $option['id'],
                'name' => $option['name'],
                'position' => $option['position'],
                'values' => $option['values'],
            ];

            $local_option = $inv_options->whereInventoryId($local_listing->uuid)
                ->whereInventoryPlatformId($option['id'])
                ->first();

            if(is_null($local_option))
            {
                $local_option = $inv_options->insert($args);
            }
            else
            {
                foreach($args as $col => $val)
                {
                    $local_option->$col = $val;
                }

                $local_option->save();
            }
        }
    }
}
