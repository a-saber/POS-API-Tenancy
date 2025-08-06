<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    // ✅ List Expenses with search and pagination
    public function index(Request $request)
    {
        $search = $request->query('search');
        $branch_id = $request->query('branch_id');
        $expense_category_id = $request->query('expense_category_id');
        $user_id = $request->query('user_id');
        $expense_date = $request->query('expense_date');
        $perPage = $request->query('per_page', 10); // default 10

        $expenses = Expense::with(['branch', 'expenseCategory', 'user'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($branch_id, function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->when($expense_category_id, function ($query) use ($expense_category_id) {
                $query->where('expense_category_id', $expense_category_id);
            })
            ->when($user_id, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->when($expense_date, function ($query) use ($expense_date) {
                $query->whereDate('expense_date', $expense_date);
            })
            ->paginate($perPage)
            ->appends($request->query()); // keep filters in pagination links

        $expenses->getCollection()->transform(function ($expense) {
            return [
                'id' => $expense->id,
                'name' => $expense->name,
                'amount' => $expense->amount,
                'branch' => optional($expense->branch)->name,
                'expense_category' => optional($expense->expenseCategory)->name,
                'user' => optional($expense->user)->name,
                'expense_date' => $expense->expense_date,
            ];
        });

        return response()->json($expenses);
    }

    // ✅ Store new Expense
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'string',
            'amount' => 'required|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'user_id' => 'required|exists:users,id',
            'expense_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: decrease branch balance

        $data = $validator->validated();
        $expense = Expense::create($data);
        return response()->json([
            'message' => 'Expense created successfully.',
            'expense' => $expense
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return $expense;
    }    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'string',
            'amount' => 'required|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'user_id' => 'required|exists:users,id',
            'expense_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $expense->update($data);
        return response()->json([
            'message' => 'Expense updated successfully.',
            'expense' => $expense
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json([
            'message' => 'Expense deleted successfully.'
        ]);
    }
}
