<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alokasi uang masuk ke tagihan (atau ke saldo siswa bila ada kelebihan).
 *
 * jenis='tagihan': tagihan_idtagihan terisi; jenis='saldo': tagihan_idtagihan NULL.
 */
class PembayaranAlokasi extends Model
{
    protected $table = 'pembayaran_alokasi';

    protected $primaryKey = 'idpembayaran_alokasi';

    protected $fillable = [
        'pembayaran_idpembayaran',
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'nominal',
        'jenis',
        'referensi_tipe',
        'referensi_id',
        'keterangan',
    ];

    protected $casts = [
        'nominal' => 'integer',
    ];

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_idpembayaran', 'idpembayaran');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }
}
