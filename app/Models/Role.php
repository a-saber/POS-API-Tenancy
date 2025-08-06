<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sales',
        'purchase',
        'users',
        'roles',
        'settings',
        'categories',
        'products',
        'units',
        'branches',
        'customers',
        'expense_categories',
        'expenses',
        'purchase_return',
        'sale_return',
        'suppliers',
        'taxes',
        'discounts',
    ];
    protected function casts(): array
    {
        return [
            'sales' => 'boolean',
            'purchase' => 'boolean',
            'users' => 'boolean',
            'roles' => 'boolean',
            'settings' => 'boolean',
            'categories' => 'boolean',
            'products' => 'boolean',
            'units' => 'boolean',
            'branches' => 'boolean',
            'customers' => 'boolean',
            'expense_categories' => 'boolean',
            'expenses' => 'boolean',
            'purchase_return' => 'boolean',
            'sale_return' => 'boolean',
            'suppliers' => 'boolean',
            'taxes' => 'boolean',
            'discounts' => 'boolean',
        ];
    }    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    /**
     * Check if the role has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return (bool) $this->{$permission};
    }
}
