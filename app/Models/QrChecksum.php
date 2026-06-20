<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrChecksum extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'checksum',
        'qr_data',
        'qr_data_hash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
