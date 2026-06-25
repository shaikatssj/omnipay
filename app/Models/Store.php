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
        'theme_color',
        'custom_css',
        'hide_branding',
        'checkout_layout',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'store_user')->withPivot('role')->withTimestamps();
    }

    public function configs()
    {
        return $this->hasMany(StorePaymentConfig::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentLinks()
    {
        return $this->hasMany(PaymentLink::class);
    }
}
