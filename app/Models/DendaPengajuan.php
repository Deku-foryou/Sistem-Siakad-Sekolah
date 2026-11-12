<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan pembebasan denda (dua tahap: admin mengajukan → superadmin memutuskan).
 * Riwayat tidak berubah oleh penerapan — efek finansial via denda_dibebaskan + tagihan_adjustment.
 */
class DendaPengajuan extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_TERPROSES = 'terproses';

    public const STATUS_GAGAL = 'gagal';

    protected $table = 'denda_pengajuan';

    protected $primaryKey = 'id';

    protected $fillable = [
        'denda_iddenda',
        'tagihan_idtagihan',
        'siswa_idsiswa',
        'nominal_denda',
        'alasan',
        'status',
        'diajukan_oleh',
        'diproses_oleh',
        'diproses_pada',
        'alasan_ditolak',
        'catatan_proses',
    ];

    protected $casts = [
        'nominal_denda' => 'integer',
        'diproses_pada' => 'datetime',
    ];

    public function denda(): BelongsTo
    {
        return $this->belongsTo(Denda::class, 'denda_iddenda', 'iddenda');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh', 'id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh', 'id');
    }
}
