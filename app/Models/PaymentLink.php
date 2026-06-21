<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    protected $fillable = [
        'store_id', 'identifier', 'name', 'amount', 'currency', 'description', 'is_active'
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'is_active' => 'boolean'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
