<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat pengiriman pengingat tagihan (untuk idempotensi & audit).
 */
class TagihanPengingat extends Model
{
    protected $table = 'tagihan_pengingat';

    protected $primaryKey = 'id';

    protected $fillable = [
        'tagihan_idtagihan',
        'tahap',
        'siswa_idsiswa',
        'penerima_user_iduser',
        'notifikasi_idnotifikasi',
        'dikirim_pada',
    ];

    protected $casts = [
        'dikirim_pada' => 'datetime',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }
}
