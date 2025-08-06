<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    // ✅ List Expenses with search and pagination
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $suppliers = Supplier::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%");
            });
        })->paginate($perPage);

        return response()->json($suppliers);
    }
    // ✅ Store new Resource
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:suppliers,name',
            'phone' => 'nullable|string|unique:suppliers,phone',
            'email' => 'nullable|email|unique:suppliers,email',
            'address' => 'nullable|string|unique:suppliers,address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $supplier = Supplier::create($data);
        return response()->json([
            'message' => 'Supplier created successfully.',
            'supplier' => $supplier
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return $supplier;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:suppliers,name,' . $supplier->id,
            'phone' => 'nullable|string|unique:suppliers,phone,' . $supplier->id,
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string|unique:suppliers,address,' . $supplier->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $supplier->update($data);
        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => $supplier
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
            return response()->json([
                'message' => 'Supplier deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete supplier.',
                'error' => $e->getCode() == 23000 ?
                    'This supplier is used in another resource.' : 
                    $e->getMessage()
            ], 500);
        }
    }
}
