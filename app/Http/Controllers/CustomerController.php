<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;
class CustomerController extends Controller
{
    // ✅ List Expenses with search and pagination
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $customers = Customer::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%");
            });
        })->paginate($perPage);

        return response()->json($customers);
    }
    // ✅ Store new Resource
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:customers,name',
            'phone' => 'nullable|string|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'address' => 'nullable|string|unique:customers,address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $customer = Customer::create($data);
        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return $customer;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:customers,name,' . $customer->id,
            'phone' => 'nullable|string|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string|unique:customers,address,' . $customer->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $customer->update($data);
        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return response()->json([
                'message' => 'Customer deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete customer.',
                'error' => $e->getCode() == 23000 ?
                    'This customer is used in another resource.' : 
                    $e->getMessage()
            ], 500);
        }
    }
}
