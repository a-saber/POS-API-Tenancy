<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'total',
        'user_id',
        'branch_id',
        'supplier_id',
        'purchase_return_id',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'user_id' => 'integer',
            'branch_id' => 'integer',
            'supplier_id' => 'integer',
            'purchase_return_id' => 'integer',
        ];
    }


    // Relations

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }
}
