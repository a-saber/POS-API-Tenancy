<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Customer;
use App\Models\Tax;
use App\Models\Discount;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;
class SaleController extends Controller
{
    // ✅ List sales with pagination and optional search
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $sales = Sale::with(['customer', 'user', 'branch', 'tax', 'discount', 'saleProducts.product', 'saleProducts.unit'])
            ->when($search, function ($query, $search) {
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($sales);
    }

    // ✅ Create a new sale
    public function store(Request $request)
    {
        $request->validate([
            'total' => 'required|numeric',
            'payment_method' => 'required|in:cash,online',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_id' => 'nullable|exists:discounts,id',
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sales_return_id' => 'nullable|exists:sales_returns,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $saleProducts = [];

            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $product->price;
                $unitId = $product->unit_id;
                $qty = $item['quantity'];
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;

                $saleProducts[] = [
                    'product_id' => $product->id,
                    'unit_id'    => $unitId,
                    'price'      => $price,
                    'quantity'   => $qty,
                ]; 
            }
            // ===== Apply Discount =====
            $discountAmount = 0;
            if ($request->filled('discount_id')) {
                $discount = Discount::findOrFail($request->discount_id);
                $discountAmount = $discount->type === 'percentage'
                    ? $subtotal * ($discount->value / 100)
                    : $discount->value;
            }
            // ===== Apply Tax =====
            $taxAmount = 0;
            if ($request->filled('tax_id')) {
                $tax = Tax::findOrFail($request->tax_id);
                $taxAmount = ($subtotal - $discountAmount) * ($tax->percentage / 100);
            }

            // ===== Final Total =====
            $calculatedTotal = round($subtotal - $discountAmount + $taxAmount, 2);
           
            // ===== Validate Provided Total =====
            if (round($request->total, 2) !== $calculatedTotal) {
                return response()->json([
                    'message' => 'Provided total does not match calculated total.',
                    'provided_total' => $request->total,
                    'calculated_total' => $calculatedTotal
                ], 422);
            }

            // ===== Save Sale =====
            $sale = Sale::create($request->only([
                'total', 'payment_method', 'tax_id', 'discount_id',
                'user_id', 'branch_id', 'customer_id', 'sales_return_id'
            ]));
            
            
            foreach ($saleProducts as $item) {
                $item['sale_id'] = $sale->id;
                SaleProduct::create($item);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale created successfully.',
                'sale' => $sale->load(['saleProducts.product', 'saleProducts.unit', 'customer', 'tax', 'discount'])
            ], 201);
        } 
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating sale.', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Show single sale
    public function show($id)
    {
        $sale = Sale::with([
            'saleProducts.product',
            'saleProducts.unit',
            'customer',
            'tax',
            'discount',
            'user',
            'branch',
        ])->findOrFail($id);

        return response()->json($sale);
    }

    // ✅ Update a sale and its products
    public function update(Request $request, $id)
    {
        $request->validate([
            'total' => 'required|numeric',
            'payment_method' => 'required|in:cash,online',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_id' => 'nullable|exists:discounts,id',
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sales_return_id' => 'nullable|exists:sales_returns,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.unit_id' => 'required|exists:units,id',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $sale = Sale::findOrFail($id);

        DB::beginTransaction();

        try {
            $sale->update($request->only([
                'total', 'payment_method', 'tax_id', 'discount_id',
                'user_id', 'branch_id', 'customer_id', 'sales_return_id'
            ]));

            // Remove existing products
            $sale->saleProducts()->delete();

            // Add new products
            foreach ($request->products as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale updated successfully.',
                'sale' => $sale->load(['saleProducts.product', 'saleProducts.unit', 'customer', 'tax', 'discount'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating sale.', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete a sale and its products
    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        DB::beginTransaction();

        try {
            $sale->saleProducts()->delete();
            $sale->delete();

            DB::commit();

            return response()->json(['message' => 'Sale deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting sale.', 'error' => $e->getMessage()], 500);
        }
    }

}
