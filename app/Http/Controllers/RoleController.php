<?php

namespace App\Http\Controllers;

use App\Data\RoleData;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Display a listing of the assignable roles.
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = auth()->user();
        $query = Role::query();

        if ($authUser->role->slug !== 'super_admin') {
            $query->where('slug', '!=', 'super_admin');
        }

        $roles = $query->orderBy('name')->get();

        return $this->success(RoleData::collect($roles));
    }
}
