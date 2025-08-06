<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Models\Branch;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity',
        'last_updated_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'branch_id' => 'integer',
        'quantity' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    // Relationships

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
