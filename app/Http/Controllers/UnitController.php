<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get All with pagination and search
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $units = Unit::when($search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->paginate($perPage);

        
        return response()->json($units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validate 
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:units,name|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $unit = Unit::create($data);
        return response()->json([
            'message' => 'Unit created successfully.',
            'unit' => $unit
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        // get by id
        return $unit;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        // validate 
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:units,name|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $unit->update($data);
        return response()->json([
            'message' => 'Unit updated successfully.',
            'unit' => $unit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        try{
            // delete by id
            $unit->delete();
            return response()->json([
                'message' => 'Unit deleted successfully.'
            ]);
        }catch(\Exception $e){
            // check if error is that the unit has products
            return response()->json([
                'message' => 'Something went wrong while deleting the unit.',
                'error' => $e->getCode() == 23000 ?
                'Cannot delete unit because there are products associated with it.':
                $e->getMessage()
            ], 500);
        }
    }
}
