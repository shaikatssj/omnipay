<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function configs()
    {
        return $this->hasMany(StorePaymentConfig::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
