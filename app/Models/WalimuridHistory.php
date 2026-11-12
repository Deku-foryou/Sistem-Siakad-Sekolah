<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Desain final §5.5 — histori relasi (append-only).
 * `periode_diubah` + empat kolom tanggal (`tanggal_*_sebelum` / `tanggal_*_sesudah`) mencatat pergeseran akses (T1d).
 * Tidak boleh diubah/dihapus dari aplikasi.
 */
class WalimuridHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'walimurid_history';

    protected $primaryKey = 'id';

    protected $fillable = [
        'walimurid_id',
        'user_iduser',
        'siswa_idsiswa',
        'aktor_id',
        'aktor_peran',
        'event',
        'status_sebelum',
        'status_sesudah',
        'tanggal_mulai_sebelum',
        'tanggal_mulai_sesudah',
        'tanggal_berhenti_sebelum',
        'tanggal_berhenti_sesudah',
        'alasan',
        'created_at',
    ];

    protected $casts = [
        'tanggal_mulai_sebelum' => 'date',
        'tanggal_mulai_sesudah' => 'date',
        'tanggal_berhenti_sebelum' => 'date',
        'tanggal_berhenti_sesudah' => 'date',
    ];

    public const EVENT = ['pengajuan', 'verifikasi', 'penolakan', 'pemutusan', 'pengajuan_ulang', 'permintaan_putus', 'permintaan_putus_ditolak', 'permintaan_putus_dibatalkan', 'periode_diubah'];

    public const AKTOR_PERAN = ['superadmin', 'admin', 'wali', 'sistem'];

    public function relasi()
    {
        return $this->belongsTo(Walimurid::class, 'walimurid_id', 'idwalimurid');
    }

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function aktor()
    {
        return $this->belongsTo(User::class, 'aktor_id', 'id');
    }
}
