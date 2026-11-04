<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = satu pertemuan jadwal pada tanggal tertentu.
 * Snapshot disimpan sebagai kolom mandiri agar histori tidak berubah
 * ketika jadwal sumber (jadwalkelas) atau jamhari-nya berubah.
 */
class JadwalPertemuan extends Model
{
    protected $table = 'jadwal_pertemuan';

    protected $primaryKey = 'idjadwal_pertemuan';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'jadwalkelas_idjadwalkelas',
        'semester_idsemester',
        'kelas_idkelas',
        'guru_idguru',
        'mapel_idmapel',
        'hari',
        'jam_ke',
        'waktu_mulai',
        'waktu_selesai',
        'tanggal',
        'status',
        'keterangan_pembatalan',
        'dibatalkan_pada',
        'dibatalkan_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dibatalkan_pada' => 'datetime',
        'hari' => 'integer',
        'jam_ke' => 'integer',
    ];

    public function jadwalkelas()
    {
        return $this->belongsTo(JadwalKelas::class, 'jadwalkelas_idjadwalkelas', 'idjadwalkelas');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_idguru', 'idguru');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_idmapel', 'idmapel');
    }

    public function dibatalkanOleh()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh', 'id');
    }
}
