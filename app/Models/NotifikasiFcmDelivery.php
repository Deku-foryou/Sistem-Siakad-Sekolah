<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status pengiriman FCM per token per notifikasi.
 *
 * Dipakai untuk membedakan pengiriman penuh, parsial, token invalid, dan
 * kegagalan yang harus di-retry. Tidak pernah diekspos ke API response.
 */
class NotifikasiFcmDelivery extends Model
{
    protected $table = 'notifikasi_fcm_delivery';

    protected $primaryKey = 'idnotifikasi_fcm_delivery';

    protected $fillable = [
        'notifikasi_idnotifikasi',
        'fcm_token_id',
        'status',
        'message_id',
        'error',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function notifikasi()
    {
        return $this->belongsTo(Notifikasi::class, 'notifikasi_idnotifikasi', 'idnotifikasi');
    }

    public function deviceToken()
    {
        return $this->belongsTo(FcmDeviceToken::class, 'fcm_token_id', 'idfcmdevicetokens');
    }
}
