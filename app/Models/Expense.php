<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ExpenseCategory;
use App\Models\Branch;
use App\Models\User;

class Expense extends Model
{
    protected $fillable = [
        'note',
        'amount',
        'expense_date',
        'branch_id',
        'expense_category_id',
        'user_id',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'expense_category_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    // Relations

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
