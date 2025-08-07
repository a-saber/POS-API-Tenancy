<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\CentralUser; 
class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        // Save tenant context
        $tenant = tenant();

        // Switch to central DB and create user there
        tenancy()->end();

        $centralUser = CentralUser::create([
            'email' => $validated['email'],
            'tenant_id' => $tenant->id,
        ]);

        // Return to tenant DB
        tenancy()->initialize($tenant);

        // Create user in tenant DB
        $tenantUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => true,
            'password' => Hash::make($validated['password']),
            'central_user_id' => $centralUser->id,
            'role_id' => $validated['role_id'],
        ]);

        $tenantUser->branches()->attach($validated['branch_ids']);

        return response()->json([
            'status' => true,
            'message' => 'User created successfully.',
            'user' => $tenantUser->load('role', 'branches'),
        ]);
    }    
}
