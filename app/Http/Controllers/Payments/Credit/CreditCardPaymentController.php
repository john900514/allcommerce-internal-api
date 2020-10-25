<?php

namespace App\Http\Controllers\Payments\Credit;

use App\Actions\Payments\Credit\CaptureCreditCardPayment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Actions\Payments\Credit\AuthorizeCreditCardPayment;

class CreditCardPaymentController extends Controller
{
    protected $request;
    /**
     * Create a new controller instance.
     * @param Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function authorize_card(AuthorizeCreditCardPayment $action)
    {
        /**
         * Business Rules
         * 1. Returning just false ensures no payment was attempted
         * 2. returning a reason mean a payment was attempted and denied
         * 3. returning tru should include a transaction uuid.
         */
        $results = $action->execute($this->request->all());

        return response($results);
    }

    public function capture_card(CaptureCreditCardPayment $action)
    {
        $results = $action->execute($this->request->all());

        return response($results);
    }
}
