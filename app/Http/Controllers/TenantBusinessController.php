<?php

namespace App\Http\Controllers;

use App\Data\TenantBusinessData;
use App\Data\UserData;
use App\Enum\Status;
use App\Http\Requests\TenantBusiness\StoreTenantBusinessRequest;
use App\Http\Requests\TenantBusiness\UpdateTenantBusinessRequest;
use App\Http\Requests\TenantBusiness\RegisterBusinessRequest;
use App\Models\Role;
use App\Models\TenantBusiness;
use App\Models\User;
use App\Support\UuidResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        $query = TenantBusiness::query();

        if ($user->role->slug !== 'super_admin') {
            $query->where('id', $user->tenant_business_id);
        }

        $businesses = $query->latest()->paginate($request->input('per_page', 15));
        return TenantBusinessData::collect($businesses, PaginatedDataCollection::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantBusinessRequest $request): JsonResponse
    {
        $user = auth()->user();
        if ($user->role->slug !== 'super_admin') {
            return $this->error(null, 'Unauthorized to create new business profile.', Response::HTTP_FORBIDDEN);
        }
        $data = $request->validated();
        $data['plan_id'] = UuidResolver::id('plans', $data['plan_uuid']);
        unset($data['plan_uuid']);

        $business = TenantBusiness::create($data);
        return $this->success($business, 'Business created successfully.', Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(TenantBusiness $tenantBusiness): JsonResponse
    {
        $user = auth()->user();

        if ($user->role->slug !== 'super_admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized to view this business profile.', Response::HTTP_FORBIDDEN);
        }

        return $this->success(TenantBusinessData::from($tenantBusiness), 'Business retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantBusinessRequest $request, TenantBusiness $tenantBusiness): JsonResponse
    {
        $user = auth()->user();

        if ($user->role->slug !== 'super_admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized to update this business profile.', Response::HTTP_FORBIDDEN);
        }

        $tenantBusiness->update($request->validated());
        return $this->success(TenantBusinessData::from($tenantBusiness), 'Business updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TenantBusiness $tenantBusiness): JsonResponse
    {
        $user = auth()->user();

        if ($user->role->slug !== 'super_admin' && $user->tenant_business_id !== $tenantBusiness->id) {
            return $this->error(null, 'Unauthorized. You can only delete your own business profile.', Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($tenantBusiness) {
            $tenantBusiness->users()->delete();
            $tenantBusiness->delete();
        });

        return $this->success(null, 'Business and its associated users have been successfully deleted.');
    }

    /**
     * Register a brand new business and primary admin user.
     */
    public function registerBusiness(RegisterBusinessRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated) {
            $business = TenantBusiness::create([
                'plan_id'          => UuidResolver::id('plans', $validated['plan_uuid']),
                'name'             => $validated['business_name'],
                'email'            => $validated['business_email'],
                'phone'            => $validated['business_phone'],
                'business_address' => $validated['business_address'] ?? null,
                'status'           => Status::INACTIVE->value,
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