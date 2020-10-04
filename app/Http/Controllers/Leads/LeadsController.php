<?php

namespace App\Http\Controllers\Leads;

use App\Actions\Leads\CreateLeadByEmail;
use App\Actions\Leads\CreateLeadByShippingAddress;
use App\Actions\Leads\CreateOrUpdateLeadByEmail;
use App\Actions\Leads\CreateOrUpdateLeadByShippingAddress;
use App\Actions\Leads\UpdateLeadByBillingAddress;
use App\Actions\Leads\UpdateLeadByEmail;
use App\Actions\Leads\UpdateLeadByShippingAddress;
use App\Aggregates\Orders\ShopifyOrderAggregate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

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

    public function createOrUpdate(CreateOrUpdateLeadByEmail $emailAction, CreateOrUpdateLeadByShippingAddress $shipAction)
    {
        $results = ['success' => false, 'reason' => 'Unsupported Reference.'];

        $data = $this->request->all();

        $validated = Validator::make($data, [
            'attributes' => 'bail|required|array',
            'lead_uuid'  => 'sometimes|required|exists:leads,id',
            'attributes.reference' => 'bail|required|in:email,shipping',
            'attributes.value' => 'bail|required',
            'attributes.checkoutType' => 'bail|required|in:checkout_funnel',
            'attributes.checkoutId' => 'bail|required',
            'attributes.shopUuid' => 'bail|required|exists:shops,id',
            'attributes.emailList' => 'sometimes|required|boolean',
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
            $data_attrbutes = $data['attributes'];
            // Eval for the reference
            switch($data_attrbutes['reference'])
            {
                case 'email':
                    $results = $emailAction->execute($data);
                    break;

                case 'shipping':
                    $results = $shipAction->execute($data);
                    break;
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
}
