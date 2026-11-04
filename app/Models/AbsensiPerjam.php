<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = catatan kehadiran satu siswa pada satu pertemuan jadwal.
 * Unik per (pertemuan, siswa). Tidak ada business logic di tahap ini.
 */
class AbsensiPerjam extends Model
{
    protected $table = 'absensi_perjam';

    protected $primaryKey = 'idabsensi_perjam';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'jadwal_pertemuan_idjadwal_pertemuan',
        'siswa_idsiswa',
        'status',
        'keterangan',
        'diinput_oleh',
        'diubah_oleh',
    ];

    public function jadwalPertemuan()
    {
        return $this->belongsTo(JadwalPertemuan::class, 'jadwal_pertemuan_idjadwal_pertemuan', 'idjadwal_pertemuan');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function diinputOleh()
    {
        return $this->belongsTo(User::class, 'diinput_oleh', 'id');
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'id');
    }
}
