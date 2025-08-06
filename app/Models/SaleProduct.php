<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleProduct extends Model
{
    protected $table = 'sales_products';
    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'sale_id' => 'integer',
            'product_id' => 'integer',
            'unit_id' => 'integer',
            'price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    // Relations
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

}
