<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'title',
        'type',
        'value',
    ];
    
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    // 🔗 Optional: relation to sales
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

}
