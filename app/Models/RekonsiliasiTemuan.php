<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temuan (anomali/ketidakcocokan) satu pemeriksaan rekonsiliasi (bukan ledger keuangan).
 */
class RekonsiliasiTemuan extends Model
{
    protected $table = 'rekonsiliasi_temuan';

    protected $fillable = [
        'rekonsiliasi_hasil_id',
        'kategori',
        'referensi_tipe',
        'referensi_id',
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'nominal_selisih',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'referensi_id' => 'integer',
        'nominal_selisih' => 'integer',
        'siswa_idsiswa' => 'integer',
        'tagihan_idtagihan' => 'integer',
    ];

    public function hasil(): BelongsTo
    {
        return $this->belongsTo(RekonsiliasiHasil::class, 'rekonsiliasi_hasil_id', 'id');
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
