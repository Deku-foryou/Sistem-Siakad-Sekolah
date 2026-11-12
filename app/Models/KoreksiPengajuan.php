<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan koreksi keuangan — diterapkan lewat reversal + replacement,
 * histori pembayaran asli dan alokasi lamanya tidak diubah.
 */
class KoreksiPengajuan extends Model
{
    protected $table = 'koreksi_pengajuan';

    protected $fillable = [
        'pembayaran_idpembayaran',
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'tagihan_pengganti_idtagihan',
        'nominal_usulan',
        'metode_usulan',
        'nama_pembayar_usulan',
        'tanggal_pembayaran_usulan',
        'jenis',
        'alasan',
        'snapshot_sebelum',
        'status',
        'diajukan_oleh',
        'disetujui_oleh',
        'disetujui_pada',
        'diproses_oleh',
        'diproses_pada',
        'alasan_ditolak',
        'catatan_proses',
    ];

    protected $casts = [
        'nominal_usulan' => 'integer',
        'snapshot_sebelum' => 'array',
        'disetujui_pada' => 'datetime',
        'diproses_pada' => 'datetime',
        'tanggal_pembayaran_usulan' => 'date',
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

    public function tagihanPengganti(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_pengganti_idtagihan', 'idtagihan');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh', 'id');
    }

    public function pemberiPersetujuan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh', 'id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh', 'id');
    }
}
