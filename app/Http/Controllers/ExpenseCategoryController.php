<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    // ✅ List taxes with search and pagination
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $expenseCategories = ExpenseCategory::when($search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->paginate($perPage);

        return response()->json($expenseCategories);
    }
    // ✅ Store new ExpenseCategory
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:expense_categories',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $expenseCategory = ExpenseCategory::create($data);
        return response()->json([
            'message' => 'Expense Category created successfully.',
            'expenseCategory' => $expenseCategory
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseCategory $expenseCategory)
    {
        // get by id
        return $expenseCategory;
    }   
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $expenseCategory->update($data);
        return response()->json([
            'message' => 'Expense Category updated successfully.',
            'expenseCategory' => $expenseCategory
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        try {
            // delete by id
            $expenseCategory->delete();
            return response()->json([
                'message' => 'Expense Category deleted successfully.'
            ]);
        }   catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while deleting the expense category.',
                'error' => $e->getCode() == 23000 ?
                'Cannot delete expense category because there are Expenses associated with it.':
                $e->getMessage()
            ], 500);
        }
    }
}
