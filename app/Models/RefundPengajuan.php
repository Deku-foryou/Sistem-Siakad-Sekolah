<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan refund pembayaran. Bukan memodifikasi transaksi asli —
 * efek finansial hanya melalui pembayaran_alokasi reversal-refund.
 */
class RefundPengajuan extends Model
{
    protected $table = 'refund_pengajuan';

    protected $fillable = [
        'pembayaran_idpembayaran',
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'nominal_refund',
        'alasan',
        'metode',
        'referensi',
        'status',
        'nomor_dokumen',
        'ref_provider',
        'diajukan_oleh',
        'disetujui_oleh',
        'disetujui_pada',
        'diproses_oleh',
        'diproses_pada',
        'alasan_ditolak',
        'catatan_proses',
    ];

    protected $casts = [
        'nominal_refund' => 'integer',
        'disetujui_pada' => 'datetime',
        'diproses_pada' => 'datetime',
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

    public function dokumen(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RefundDokumen::class, 'refund_pengajuan_id', 'id');
    }
}
