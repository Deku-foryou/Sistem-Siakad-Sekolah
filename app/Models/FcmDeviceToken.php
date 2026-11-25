<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Token perangkat FCM — satu baris per perangkat (unik per token).
 * Never exposed via API responses.
 */
class FcmDeviceToken extends Model
{
    protected $table = 'fcm_device_tokens';

    protected $primaryKey = 'idfcmdevicetokens';

    protected $fillable = [
        'user_iduser',
        'fcm_token',
        'platform',
        'app_version',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }
}
