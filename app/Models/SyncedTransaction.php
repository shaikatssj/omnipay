<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'sender',
        'amount',
        'trxid',
        'raw_message',
        'meta_data',
        'is_used',
        'timestamp',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'amount' => 'float',
        'timestamp' => 'integer',
        'meta_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
