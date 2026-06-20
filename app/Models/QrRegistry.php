<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrRegistry extends Model
{
    protected $table = 'qr_registry';

    protected $fillable = [
        'qr_data_hash',
        'owner_user_id',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
