<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Inventory;

class Branch extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email'];
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_branches');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Branch has many sales
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // Branch has many purchases
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    // Branch has many inventories
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
