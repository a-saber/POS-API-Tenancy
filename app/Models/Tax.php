<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'title',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
        ];
    }

    // 🔗 Optional: relation to sales
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

}
