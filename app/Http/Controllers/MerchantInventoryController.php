<?php

namespace App\Http\Controllers;


use App\InventoryImages;
use App\InventoryVariants;
use App\MerchantInventory;
use App\VariantsOptions;
use Illuminate\Http\Request;

class MerchantInventoryController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $results = ['success' => false, 'reason' => 'Invalid Request'];

        $user = auth()->user();
        $merchant = $user->merchant();

        if(!is_null($merchant))
        {
            $inventory = $merchant->inventory()
                ->with('variants')
                ->with('images')
                ->get();

            if(count($inventory) > 0)
            {
                $results = ['success' => true, 'inventory' => $inventory->toArray()];
            }
            else
            {
                $results['reason'] = 'No Inventory Available';
            }
        }

        return response()->json($results);
    }

    public function rogue_import_biatch(MerchantInventory $inventory,
                                        InventoryImages $images,
                                        InventoryVariants $variants,
                                        VariantsOptions $options)
    {
        $results = ['success' => false, 'reason' => 'Fuck is wrong with you?'];

        $data = $this->request->all();
        $user = auth()->user();
        $merchant = $user->merchant();

        foreach($data['products'] as $product)
        {
            $item = $inventory->alpha_insertShopifyItem($merchant, $product);

            if($item)
            {
                // Add Variants
                foreach($product['variants'] as $variant)
                {
                    $variants->alpha_insertShopifyVariant($merchant, $item, $variant);
                }

                // Add Options
                foreach($product['options'] as $option)
                {
                    $options->alpha_insertShopifyOption($merchant, $item, $option);
                }

                // Add Images
                foreach($product['images'] as $img)
                {
                    $images->alpha_insertShopifyImage($merchant, $item, $img);
                }
            }
        }

        return response()->json($results);
    }


}
