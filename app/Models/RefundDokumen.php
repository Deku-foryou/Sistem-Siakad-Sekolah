<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen resmi (PDF) untuk refund yang berhasil diproses.
 *
 * Dibuat hanya setelah refund terproses & reversal ledger tersimpan.
 * Snapshot tersimpan saat terbit — dokumen lama tidak berubah meski
 * identitas sekolah / data terkait berubah.
 */
class RefundDokumen extends Model
{
    protected $table = 'refund_dokumen';

    protected $primaryKey = 'id';

    protected $fillable = [
        'refund_pengajuan_id',
        'nomor_dokumen',
        'file_path',
        'checksum_sha256',
        'ukuran_file',
        'snapshot_sekolah',
        'snapshot_dokumen',
        'dibuat_oleh',
    ];

    protected $casts = [
        'ukuran_file' => 'integer',
        'snapshot_sekolah' => 'array',
        'snapshot_dokumen' => 'array',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(RefundPengajuan::class, 'refund_pengajuan_id', 'id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }
}
