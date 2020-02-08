<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MerchantAccountController extends Controller
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
            $permissions = $merchant->permissions($user);
            $results = [
                'success' => true,
                'merchant' => [
                    'id' => $merchant->uuid,
                    'name' => $merchant->name,
                    'joinDate' => $merchant->created_at,
                    'active' => $merchant->active == 1,
                    'permissions' => $permissions
                ]
            ];
        }

        return response()->json($results);
    }
}
