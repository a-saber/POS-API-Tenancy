<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Tenant;
use App\Models\User;
use Stancl\Tenancy\TenantManager;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use App\Models\CentralUser;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // ✅ Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:central_users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // ✅ Step 2: Create tenant
            $uuid = Str::uuid();
            $slugEmail = Str::slug(explode('@', $request->email)[0]); // safer for domain use
            $tenantId = $slugEmail . '_' . $uuid;
            $domain = $slugEmail . '.localhost'; // e.g. "ahmed.localhost"
            
            // Check if domain already exists (prevent duplicate domain exception)
            if (Domain::where('domain', $domain)->exists()){
                throw new \Exception('Domain already taken. Please use a different email.');
            }
            
            $tenant = Tenant::create([
                'id' => $tenantId,
            ]);

            $centralUser = CentralUser::create([
                'email'     => $request->email,
                'tenant_id' => $tenantId,
            ]);

            // ✅ Step 3: Attach domain (e.g. "ahmed.localhost")
            $tenant->domains()->create([
                'domain' => $domain, 
            ]);

            tenancy()->initialize($tenant);

            // ✅ Step 4: Run logic inside tenant
            $tenant->run(function () use ($request, $centralUser) {
                // Create first user in tenant DB
                User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'central_user_id' => $centralUser->id,
                ]);
            });

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tenant and user registered successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'   => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            // ✅ Step 1: Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:central_users,email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Step 1: Find email in central_users (central DB)
            $centralUser = CentralUser::where('email', $request->email)->first();

            if (! $centralUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not found.',
                ], 404);
            }

            // Step 2: Switch to tenant DB
            tenancy()->initialize($centralUser->tenant_id);

            // Step 3: Find user in tenant DB
            $tenantUser = User::where('email', $request->email)->first();

            if (! $tenantUser ) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found in tenant.',
                ], 404);
            }
            // Step 3: Check password
            if (! Hash::check($request->password, $tenantUser->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password.',
                ], 401);
            }

            // Step 4: Create Sanctum token
            $token = $tenantUser->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $tenantUser,
                'domain' => $centralUser->tenant->domains->first()->domain,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        } finally {
            // Clean up tenancy context
            tenancy()->end();
        }
    }
    
}
