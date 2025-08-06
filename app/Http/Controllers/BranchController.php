<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Support\Facades\Validator;
class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get All with pagination and search
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $branches = Branch::when($search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->paginate($perPage);

        
        return response()->json($branches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:branches,name|max:255',
            'address' => 'nullable|string|unique:branches,address',
            'phone' => 'nullable|string|unique:branches,phone',
            'email' => 'nullable|email|unique:branches,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $branch = Branch::create($data);
        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => $branch
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        // get by id
        return $branch;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        // validate 
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|unique:branches,name,' . $branch->id . '|max:255',
            'address' => 'nullable|string|unique:branches,address,' . $branch->id,
            'phone' => 'nullable|string|unique:branches,phone,' . $branch->id,
            'email' => 'nullable|email|unique:branches,email,' . $branch->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $validator->validated();
        $branch->update($data);
        return response()->json([
            'message' => 'Branch updated successfully.',
            'branch' => $branch
        ]);
        
    }

    /**
     * Remove the specified resource from storage.
     */
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
                'Cannot delete branch because there are entities associated with it.':
                $e->getMessage()
            ], 500);
        }

    }
}
