<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_path',
        'barcode',
        'brand',
        'price',
        'unit_id',
        'category_id',
    ];
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'unit_id' => 'integer',
            'category_id' => 'integer',
        ];
    }
    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    public function saleProducts()
    {
        return $this->hasMany(SaleProduct::class);
    }
}
