<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name',
        'address',
        'postal_code',
        'tax_no',
        'commercial_no',
        'phone',
        'email',
        'logo_url',
    ];
}
