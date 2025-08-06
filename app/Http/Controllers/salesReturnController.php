<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class salesReturnController extends Controller
{
    // List all sales returns
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');

        $salesReturns = SalesReturn::with(['sale', 'user'])
            ->when($search, function ($query, $search) {
                $query->where('reason', 'like', '%' . $search . '%');
            })
            ->paginate($perPage);

        return response()->json($salesReturns);
    }

    // Store a new sales return
    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string',
            'returned_at' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $salesReturn = SalesReturn::create([
                'sale_id' => $request->sale_id,
                'user_id' => $request->user_id,
                'reason' => $request->reason,
                'returned_at' => $request->returned_at ?? now(),
            ]);

            // Update sale to link to this return
            $sale = $salesReturn->sale;
            $sale->sales_return_id = $salesReturn->id;
            $sale->save();

            DB::commit();

            return response()->json([
                'message' => 'Sales return created successfully.',
                'return' => $salesReturn->load(['sale', 'user']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales return creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create sales return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Show a single sales return
    public function show($id)
    {
        $salesReturn = SalesReturn::with(['sale', 'user'])->find($id);

        if (!$salesReturn) {
            return response()->json(['message' => 'Sales return not found.'], 404);
        }

        return response()->json($salesReturn);
    }

    // Update a sales return
    public function update(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string',
            'returned_at' => 'nullable|date',
        ]);

        $salesReturn = SalesReturn::find($id);

        if (!$salesReturn) {
            return response()->json(['message' => 'Sales return not found.'], 404);
        }

        try {
            DB::beginTransaction();

            $salesReturn->update([
                'reason' => $request->reason ?? $salesReturn->reason,
                'returned_at' => $request->returned_at ?? $salesReturn->returned_at,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sales return updated successfully.',
                'return' => $salesReturn->load(['sale', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales return update failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update sales return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a sales return
    public function destroy($id)
    {
        $salesReturn = SalesReturn::find($id);

        if (!$salesReturn) {
            return response()->json(['message' => 'Sales return not found.'], 404);
        }

        try {
            DB::beginTransaction();

            // Unlink from Sale
            $sale = $salesReturn->sale;
            if ($sale) {
                $sale->sales_return_id = null;
                $sale->save();
            }

            $salesReturn->delete();

            DB::commit();

            return response()->json(['message' => 'Sales return deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales return deletion failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to delete sales return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
