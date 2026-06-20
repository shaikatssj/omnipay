<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'logo',
        'is_active',
    ];

    public function configs()
    {
        return $this->hasMany(StorePaymentConfig::class);
    }
}
