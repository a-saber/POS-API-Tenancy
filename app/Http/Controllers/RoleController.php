<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
class RoleController extends Controller
{
    // ✅ List all roles
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');

        $roles = Role::when($search, function ($query, $search) {
            $query->where('name', 'like', "%$search%");
        })->paginate($perPage);

        return response()->json($roles);
    }

    // ✅ Store a new role
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
        ] + $this->booleanFields());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $fields = array_merge(['name', 'description'], array_keys($this->booleanFields()));
        $role = Role::create($request->only($fields));

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role
        ], 201);
    }

    // show role
    public function show(Role $role)
    {
        return $role;
    }  

    public function update(Request $request, Role $role)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
        ] + $this->booleanFields());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $role->update($request->only(array_keys($this->booleanFields() + ['name', 'description'])));

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role
        ]);
    }

    public function destroy(Branch $branch)
    {
        try{
            // delete
            $branch->delete();
            return response()->json([
                'message' => 'Branch deleted successfully.',
                'branch' => $branch
            ]);
        }catch(\Exception $e){
            // check if error is that the branch has sales
            return response()->json([
                'message' => 'Something went wrong while deleting the branch.',
                'error' => $e->getCode() == 23000 ?
                'Cannot delete branch because there are users associated with it.':
                $e->getMessage()
            ], 500);
        }
    }

    private function booleanFields(): array
    {
        return [
            'sales' => 'boolean',
            'purchase' => 'boolean',
            'users' => 'boolean',
            'roles' => 'boolean',
            'settings' => 'boolean',
            'categories' => 'boolean',
            'products' => 'boolean',
            'units' => 'boolean',
            'branches' => 'boolean',
            'customers' => 'boolean',
            'expense_categories' => 'boolean',
            'expenses' => 'boolean',
            'purchase_return' => 'boolean',
            'sale_return' => 'boolean',
            'suppliers' => 'boolean',
            'taxes' => 'boolean',
            'discounts' => 'boolean',
        ];
    }
}
