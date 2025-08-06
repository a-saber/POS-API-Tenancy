<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'total',
        'payment_method',
        'tax_id',
        'discount_id',
        'user_id',
        'branch_id',
        'customer_id',
        'sales_return_id',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'payment_method' => 'string',
            'tax_id' => 'integer',
            'discount_id' => 'integer',
            'user_id' => 'integer',
            'branch_id' => 'integer',
            'customer_id' => 'integer',
            'sales_return_id' => 'integer',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function products()
    {
        return $this->hasMany(SaleProduct::class);
    }
    
    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }
}
