<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    // ✅ List all purchase returns with optional search & pagination
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $returns = PurchaseReturn::with(['purchase', 'user'])
            ->when($search, function ($query, $search) {
                $query->whereHas('purchase', function ($q) use ($search) {
                    $q->where('id', $search); // or search by related fields like supplier name
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($returns);
    }

    // ✅ Create a new purchase return
    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string',
            'returned_at' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Create the purchase return
            $return = PurchaseReturn::create([
                'purchase_id' => $request->purchase_id,
                'user_id' => $request->user_id,
                'reason' => $request->reason,
                'returned_at' => $request->returned_at ?? now(),
            ]);

            // Update purchase to link to this return
            $purchase = $return->purchase;
            $purchase->purchase_return_id = $return->id;
            $purchase->save();

            DB::commit();

            return response()->json([
                'message' => 'Purchase return created and linked successfully.',
                'return' => $return->load(['purchase', 'user']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase return creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while creating the purchase return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ Show a specific purchase return
    public function show($id)
    {
        $return = PurchaseReturn::with(['purchase', 'user'])->findOrFail($id);
        return response()->json($return);
    }

    // ✅ Update a purchase return
    public function update(Request $request, $id)
    {
        $return = PurchaseReturn::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string',
            'returned_at' => 'nullable|date',
        ]);

        $return->update($request->only(['reason', 'returned_at']));

        return response()->json([
            'message' => 'Purchase return updated successfully.',
            'return' => $return->load(['purchase', 'user']),
        ]);
    }

    // ✅ Delete a purchase return
    public function destroy($id)
    {
        $return = PurchaseReturn::findOrFail($id);
        $return->delete();

        return response()->json(['message' => 'Purchase return deleted successfully.']);
    }
}
