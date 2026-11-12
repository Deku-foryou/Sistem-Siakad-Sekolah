<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Percobaan pembayaran (instruksi transaksi Snap Midtrans).
 *
 * Belum tentu menghasilkan uang masuk — bukan pengurang saldo tagihan.
 * Hanya transaksi yang terverifikasi (webhook/API) menjadi ledger (tabel pembayaran).
 */
class PaymentAttempt extends Model
{
    protected $table = 'payment_attempt';

    protected $primaryKey = 'idpayment_attempt';

    protected $fillable = [
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'order_id',
        'public_token',
        'provider',
        'metode',
        'nominal',
        'biaya_gateway',
        'status',
        'payer_user_id',
        'payer_jenis',
        'payer_nama',
        'snap_token',
        'snap_redirect_url',
        'payload_request',
        'payload_response',
        'expired_pada',
    ];

    protected $casts = [
        'nominal' => 'integer',
        'biaya_gateway' => 'integer',
        'payload_request' => 'array',
        'payload_response' => 'array',
        'expired_pada' => 'datetime',
    ];

    protected $hidden = [
        'payload_request',
        'payload_response',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'payment_attempt_id', 'idpayment_attempt');
    }
}
