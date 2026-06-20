<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePaymentConfig extends Model
{
    protected $fillable = [
        'store_id',
        'payment_method_id',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
