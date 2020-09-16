<?php

namespace App\Http\Controllers\Leads;

use App\Actions\Leads\CreateOrUpdateLeadByEmail;
use App\Actions\Leads\CreateOrUpdateLeadByShippingAddress;
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
}
