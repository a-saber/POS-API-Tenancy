<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    // ✅ List taxes with search and pagination
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $taxes = Tax::when($search, function ($query, $search) {
            $query->where('title', 'like', '%' . $search . '%');
        })->paginate($perPage);

        return response()->json($taxes);
    }

    // ✅ Store new tax
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|unique:taxes',
            'percentage' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $tax = Tax::create($data);
        return response()->json([
            'message' => 'Tax created successfully.',
            'tax' => $tax
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax)
    {
        // get by id
        return $tax;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tax $tax)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|unique:taxes,title,' . $tax->id,
            'percentage' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $tax->update($data);
        return response()->json([
            'message' => 'Tax updated successfully.',
            'tax' => $tax
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax)
    {
        try{
            // delete by id
            $tax->delete();
            return response()->json([
                'message' => 'Tax deleted successfully.'
            ]);
        }catch(\Exception $e){
            // check if error is that the unit has products
            return response()->json([
                'message' => 'Something went wrong while deleting the tax.',
                'error' => $e->getCode() == 23000 ?
                'Cannot delete tax because there are sales associated with it.':
                $e->getMessage()
            ], 500);
        }
    }

}
