<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Product;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_path',
    ];

    // Relation to products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
