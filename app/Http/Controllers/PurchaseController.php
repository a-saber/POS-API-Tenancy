<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    // ✅ List all purchases with search and pagination
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $purchases = Purchase::with(['user', 'branch', 'supplier', 'purchaseReturn', 'purchaseProducts.product', 'purchaseProducts.unit'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('supplier', fn($q) => $q->where('name', 'like', "%$search%"))
                      ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%$search%"));
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($purchases);
    }

    // ✅ Store a new purchase with its products
    public function store(Request $request)
    {
        $validated = $request->validate([
            'total' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_return_id' => 'nullable|exists:purchase_returns,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.unit_id' => 'required|exists:units,id',
            'products.*.cost_price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $purchase = Purchase::create($validated);

            foreach ($validated['products'] as $product) {
                $purchase->purchaseProducts()->create($product);
            }
            DB::commit();
            return response()->json($purchase->load(['purchaseProducts.product', 'purchaseProducts.unit']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create purchase', 'message' => $e->getMessage()], 500);
        }
    }
    // ✅ Show a specific purchase
    public function show(Purchase $purchase)
    {
        $purchase->load(['user', 'branch', 'supplier', 'purchaseReturn', 'purchaseProducts.product', 'purchaseProducts.unit']);
        return response()->json($purchase);
    }

    // ✅ Update a purchase (basic structure, can be enhanced)
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'total' => 'sometimes|required|numeric|min:0',
            'user_id' => 'sometimes|required|exists:users,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'purchase_return_id' => 'nullable|exists:purchase_returns,id',
        ]);

        $purchase->update($validated);

        return response()->json($purchase->refresh());
    }

    // ✅ Delete a purchase
    public function destroy(Purchase $purchase)
    {
        try {
            $purchase->delete();
            return response()->json([
                'message' => 'Purchase deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete purchase.',
                'error' => $e->getCode() == 23000 ?
                    'This purchase is used in another resource.' : 
                    $e->getMessage()
            ], 500);
        }

    }
}
