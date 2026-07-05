<?php

namespace App\Http\Controllers;

use App\Data\TenantBusinessData;
use App\Data\UserData;
use App\Enum\StatusEnum;
use App\Http\Requests\TenantBusiness\StoreTenantBusinessRequest;
use App\Http\Requests\TenantBusiness\UpdateTenantBusinessRequest;
use App\Http\Requests\TenantBusiness\RegisterBusinessRequest;
use App\Models\Role;
use App\Models\TenantBusiness;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\LaravelData\PaginatedDataCollection;
use Symfony\Component\HttpFoundation\Response;

class TenantBusinessController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        //TODO move this in permission feature
        if ($user->role->slug !== 'super_admin') {
            return $this->error(null, 'Unauthorized to get business list.', Response::HTTP_FORBIDDEN);
        }
        
        $query = TenantBusiness::query()->paginate($request->input('per_page', 15));
        return TenantBusinessData::collect($query, PaginatedDataCollection::class);
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
        $user = auth()->user();

        //TODO move this in permission feature
        if ($user->role->slug !== 'super_admin') {
            return $this->error(null, 'Unauthorized to create business profile.', Response::HTTP_FORBIDDEN);
        }
        $business = TenantBusiness::create($request->validated());
        return $this->success($business, 'Business created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TenantBusiness $tenantBusiness)
    {
        $user = auth()->user();

        //TODO move this in permission feature
        if ($user->role->slug !== 'super_admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized to view this business profile.', Response::HTTP_FORBIDDEN);
        }
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
    public function update(UpdateTenantBusinessRequest $request, TenantBusiness $tenantBusiness)
    {
        $user = auth()->user();
        //TODO move this in permission feature
        if ($user->role->slug !== 'super_admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized to update this business profile.', Response::HTTP_FORBIDDEN);
        }

        $tenantBusiness->update($request->validated());
        return $this->success(TenantBusinessData::from($tenantBusiness), 'Business updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TenantBusiness $tenantBusiness)
    {
        $user = auth()->user();

        //TODO move this on user permission feature
        $allowedRoles = ['super_admin', 'admin'];
        if (!in_array($user->role->slug, $allowedRoles)) {
            return $this->error(null, 'Unauthorized. Your role does not have permission to delete a business.', Response::HTTP_FORBIDDEN);
        }

        //TODO move this on user permission feature
        if ($user->role->slug === 'admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized. You can on ly delete your own business profile.', Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($tenantBusiness) {
            $tenantBusiness->users()->delete();
            $tenantBusiness->delete();
        });

        return $this->success(null, 'Business and its associated users have been successfully deleted.');
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