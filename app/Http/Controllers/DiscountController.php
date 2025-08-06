<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Discount;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    // dislay all discounts with search
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $discounts = Discount::when($search, function ($query, $search) {
            $query->where('title', 'like', '%' . $search . '%');
        })->paginate($perPage);

        return response()->json($discounts);
    }

    // store new discount
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|unique:discounts',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $discount = Discount::create($data);
        return response()->json([
            'message' => 'Discount created successfully.',
            'discount' => $discount
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Discount $discount)
    {
        // get by id
        return $discount;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Discount $discount)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|unique:discounts,title,' . $discount->id,
            'type' => 'in:percentage,fixed',
            'value' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $discount->update($data);
        return response()->json([
            'message' => 'Discount updated successfully.',
            'discount' => $discount
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discount $discount)
    {
        try{
            // delete by id
            $unit->delete();
            return response()->json([
                'message' => 'Discount deleted successfully.'
            ]);
        }catch(\Exception $e){
            // check if error is that the unit has products
            return response()->json([
                'message' => 'Something went wrong while deleting the discount.',
                'error' => $e->getCode() == 23000 ?
                'Cannot delete discount because there are sales associated with it.':
                $e->getMessage()
            ], 500);
        }
    }
}
