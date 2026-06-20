<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrBlacklist extends Model
{
    protected $table = 'qr_blacklist';

    protected $fillable = [
        'qr_data_hash',
        'note',
    ];
}
