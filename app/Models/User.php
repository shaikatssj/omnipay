<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'api_key',
        'sms_sync_key',
        'notify_invoice_created',
        'notify_invoice_paid',
        'notify_invoice_expired',
        'notify_login',
        'two_factor_method',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function syncedTransactions()
    {
        return $this->hasMany(SyncedTransaction::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_invoice_created' => 'boolean',
            'notify_invoice_paid' => 'boolean',
            'notify_invoice_expired' => 'boolean',
            'notify_login' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }
}
