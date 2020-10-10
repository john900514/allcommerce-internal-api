<?php

namespace App\Http\Controllers\Leads;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Actions\Leads\AccessDraftOrder;
use App\Actions\Leads\CreateLeadByEmail;
use App\Actions\Leads\UpdateLeadByEmail;
use Illuminate\Support\Facades\Validator;
use App\Actions\Leads\CreateOrUpdateLeadByEmail;
use App\Actions\Leads\UpdateLeadByBillingAddress;
use App\Actions\Leads\CreateLeadByShippingAddress;
use App\Actions\Leads\UpdateLeadByShippingAddress;
use App\Actions\Leads\CreateOrUpdateLeadByShippingAddress;

class LeadsController extends Controller
{
    protected $request;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // Leads via Email
    public function createWithEmail(CreateLeadByEmail $action)
    {
        $results = ['success' => false, 'reason' => 'Could not create or update lead.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'email' => 'bail|required',
            'checkoutType' => 'bail|required|in:checkout_funnel',
            'checkoutId' => 'bail|required',
            //'shopUuid' => 'bail|required|exists:shops,id',
            'emailList' => 'sometimes|required|boolean',
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
            $data['ip'] = $this->request->ip();
            if($lead = $action->execute($data))
            {
                $results = ['success' => true, 'lead_uuid' => $lead['id']];
            }
        }

        return response()->json($results);
    }

    public function updateWithEmail(UpdateLeadByEmail $action)
    {
        $results = ['success' => false, 'reason' => 'Could not create or update lead.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'email'     => 'bail|required',
            'lead_uuid' => 'bail|required|exists:leads,id',
            'emailList' => 'sometimes|required|boolean',
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
            if($lead = $action->execute($data))
            {
                $results = array_merge(['success' => true], $lead);
            }
        }

        return response()->json($results);
    }

    // Leads Via Shipping
    public function createWithShipping(CreateLeadByShippingAddress $action)
    {
        $results = ['success' => false, 'reason' => 'Could not create lead.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'shipping' => 'bail|required|array',
            'checkoutType' => 'bail|required|in:checkout_funnel',
            'checkoutId' => 'bail|required',
            //'shopUuid' => 'bail|required|exists:shops,id',

            'billing' => 'bail|required|array',
            'emailList' => 'bail|required|boolean',
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
            $data['ip'] = $this->request->ip();
            if(($response = $action->execute($data)) && (is_array($response)))
            {
                $results = array_merge(['success' => true], $response);
            }
        }

        return response()->json($results);
    }

    public function updateWithShipping(UpdateLeadByShippingAddress $action)
    {
        $results = ['success' => false, 'reason' => 'Could not update lead with the data given.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'lead_uuid' => 'bail|required|exists:leads,id',
            'shipping' => 'bail|required|array',
            'emailList' => 'sometimes|required|boolean',

            'shipping_uuid' => 'sometimes|required|exists:shipping_addresses,id',
            'billing_uuid' => 'sometimes|required|exists:billing_addresses,id',
            'billing' => 'sometimes|required|array',
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
            if(($response = $action->execute($data)) && (is_array($response)))
            {
                $results = array_merge(['success' => true], $response);
            }
        }

        return response()->json($results);
    }

    public function updateWithBilling(UpdateLeadByBillingAddress $action)
    {
        $results = ['success' => false, 'reason' => 'Could not update lead with the data given.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'lead_uuid' => 'bail|required|exists:leads,id',
            'billing_uuid' => 'bail|required|exists:billing_addresses,id',
            'billing' => 'bail|required|array',
            'emailList' => 'sometimes|required|boolean',
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
            if(($response = $action->execute($data)) && (is_array($response)))
            {
                $results = array_merge(['success' => true], $response);
            }
        }

        return response()->json($results);
    }

    // Leads-2-Shopify Draft Orders
    public function draftOrderWithShippingMethod(AccessDraftOrder $action)
    {
        $results = ['success' => false, 'reason' => 'Could not access draft order with the data given.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'leadUuid' => 'bail|required|exists:leads,id',
            'shippingMethod' => 'bail|required|array',
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
            $token = $this->request->header('x-allcommerce-token');
            $data['token'] = $token;

            if(($response = $action->execute($data)) && (is_array($response)))
            {
                $results = array_merge(['success' => true], $response);
            }
        }

        return response()->json($results);
    }
}
