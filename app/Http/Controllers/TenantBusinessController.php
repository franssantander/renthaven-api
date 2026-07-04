<?php

namespace App\Http\Controllers;

use App\Data\TenantBusinessData;
use App\Data\UserData;
use App\Enum\RoleEnum;
use App\Enum\StatusEnum;
use App\Http\Requests\RegisterBusinessRequest;
use App\Http\Requests\StoreTenantBusinessRequest;
use App\Models\Role;
use App\Models\TenantBusiness;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class TenantBusinessController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantBusinessRequest $request)
    {
        $business = TenantBusiness::create($request->validated());
        return $this->success($business, 'Business created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TenantBusiness $tenantBusiness)
    {
        return $this->success(TenantBusinessData::from($tenantBusiness), 'Business retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function registerBusiness(RegisterBusinessRequest $request)
    {
        $validated = $request->validated();
        $result = DB::transaction(function () use ($validated) {
            $business = TenantBusiness::create([
                'plan_id'          => $validated['plan_id'],
                'name'             => $validated['business_name'],
                'email'            => $validated['business_email'],
                'phone'            => $validated['business_phone'],
                'business_address' => $validated['business_address'] ?? null,
                'status'           =>  StatusEnum::INACTIVE->value,
            ]);

            $adminRole = Role::where('slug', 'admin')->firstOrFail();

            $user = User::create([
                'tenant_business_id' => $business->id,
                'role_id'            => $adminRole->id,
                'full_name'          => $validated['full_name'],
                'email'              => $validated['email'],
                'phone'              => $validated['phone'] ?? null,
                'username'           => $validated['username'],
                'password'           => Hash::make($validated['password']),
            ]);

            event(new Registered($user));

            return compact('user', 'business');
        });


        $token = $result['user']->createToken('auth_token')->accessToken;
        $cookie = cookie('auth_token', $token, 60 * 24 * 7, '/', null, app()->environment('production'), true, false, 'Strict');

        return $this->success(UserData::from($result['user']->load('role', 'tenantBusiness')))->withCookie($cookie);
    }
}
