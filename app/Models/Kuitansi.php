<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kuitansi extends Model
{
    protected $table = 'kuitansi';

    protected $primaryKey = 'idkuitansi';

    protected $fillable = [
        'pembayaran_idpembayaran',
        'siswa_idsiswa',
        'tagihan_idtagihan',
        'nomor',
        'tahun',
        'bulan',
        'nomor_urut',
        'snapshot_sekolah',
        'snapshot_kuitansi',
        'file_path',
        'checksum_sha256',
        'ukuran_file',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'nomor_urut' => 'integer',
        'ukuran_file' => 'integer',
        'snapshot_sekolah' => 'array',
        'snapshot_kuitansi' => 'array',
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

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }
}
