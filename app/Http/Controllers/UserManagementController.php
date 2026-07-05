<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Http\Requests\UserManagement\StoreUserManagementRequest;
use App\Http\Requests\UserManagement\UpdateUserManagementRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query = User::query()->with(['role', 'tenantBusiness']);

        // TODO USER PERMISSION FEATURE
        if ($authUser->role->slug !== 'super_admin') {
            $query->where('tenant_business_id', $authUser->tenant_business_id);
        }

        $users = $query->latest()->paginate($request->input('per_page', 15));

        return UserData::collect($users);
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
    public function store(StoreUserManagementRequest $request)
    {
        $authUser = auth()->user();
        $data = $request->validated();

        if ($authUser->role->slug !== 'super_admin') {
            $data['tenant_business_id'] = $authUser->tenant_business_id;
        }

        $data['password'] = Hash::make($data['password']);
        $newUser = User::create($data);
        return $this->success($newUser, 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $authUser = auth()->user();

        // TODO USER PERMISSION FEATURE
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to view this user.', Response::HTTP_FORBIDDEN);
        }

        $user->load(['role', 'tenantBusiness']);

        return $this->success(UserData::from($user), 'User retrieved successfully.');
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
    public function update(UpdateUserManagementRequest $request, User $user)
    {
        $authUser = auth()->user();

        //TODO USER PERMISSION FEATURE
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to update this user.', Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        // Only hash and update the password if a new one was provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Prevent non-super_admins from moving users to different businesses
        if ($authUser->role->slug !== 'super_admin') {
            unset($data['tenant_business_id']);
        }

        $user->update($data);

        return $this->success($user, 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $authUser = auth()->user();

        // TODO USER PERMISSION FEATURE
        if ($authUser->role->slug !== 'super_admin' && $authUser->tenant_business_id !== $user->tenant_business_id) {
            return $this->error(null, 'Unauthorized to delete this user.', Response::HTTP_FORBIDDEN);
        }

        // Prevent users from deleting themselves
        if ($authUser->id === $user->id) {
            return $this->error(null, 'You cannot delete your own account.', Response::HTTP_BAD_REQUEST);
        }

        $user->delete();

        return $this->success(null, 'User deleted successfully.');
    }
}