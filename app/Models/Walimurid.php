<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Desain final §5.2 — tabel `walimurid`: relasi wali murid ↔ siswa.
 *
 * Tiga makna aktor:
 *   user_iduser      → PEMILIK relasi (akun wali)
 *   pengaju_id       → aktor yang mengajukan (wali atau admin atas nama wali)
 *   verifikator_id   → admin yang memutuskan (NULL selama pending)
 *
 * `tanggal_mulai` (batas akses akademik) DIPERBEDAKAN dari `tanggal_verifikasi` (waktu pengesahan).
 * Data akademik di belakang tabel ini tunduk pada guard + AksesPeriodeService (desain §7.5, §8.9).
 */
class Walimurid extends Model
{
    use HasFactory;

    protected $table = 'walimurid';

    protected $primaryKey = 'idwalimurid';

    protected $fillable = [
        'user_iduser',
        'siswa_idsiswa',
        'hubungan',
        'status_hubungan',
        'tanggal_mulai',
        'tanggal_berhenti',
        'tanggal_verifikasi',
        'pengaju_id',
        'verifikator_id',
        'catatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berhenti' => 'date',
        'tanggal_verifikasi' => 'datetime',
    ];

    public const STATUS_RELATION = ['pending', 'verified', 'rejected', 'terminated'];

    public const HUBUNGAN = ['ayah', 'ibu', 'wali', 'lainnya'];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'pengaju_id', 'id');
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id', 'id');
    }

    public function permintaanPutus()
    {
        return $this->hasMany(WalimuridPermintaanPutus::class, 'walimurid_id', 'idwalimurid');
    }

    public function histories()
    {
        return $this->hasMany(WalimuridHistory::class, 'walimurid_id', 'idwalimurid');
    }
}
