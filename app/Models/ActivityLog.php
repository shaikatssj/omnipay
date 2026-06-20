<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Log a merchant/admin action.
     */
    public static function log($action, $description, $storeId = null)
    {
        try {
            $userId = \Illuminate\Support\Facades\Auth::id();
            if (!$userId) {
                return;
            }

            self::create([
                'user_id' => $userId,
                'store_id' => $storeId,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Activity Logger failed: ' . $e->getMessage());
        }
    }
}
