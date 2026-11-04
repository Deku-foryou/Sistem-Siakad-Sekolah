<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiHarian extends Model
{
    protected $table = 'absensi_harian';

    protected $primaryKey = 'idabsensi_harian';

    protected $fillable = [
        'tahunajaran_idtahunajaran',
        'semester_idsemester',
        'kelas_idkelas',
        'tanggal',
        'siswa_idsiswa',
        'status',
        'catatan',
        'diinput_oleh',
        'diubah_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function tahunajaran()
    {
        return $this->belongsTo(Tahunajaran::class, 'tahunajaran_idtahunajaran', 'idtahunajaran');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function userInput()
    {
        return $this->belongsTo(User::class, 'diinput_oleh', 'id');
    }

    public function userUbah()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeSearch($query, $value)
    {
        return $query->where('idabsensi_harian', 'like', "%{$value}%");
    }

    public function scopeForKelas($query, $kelasId)
    {
        return $query->where('kelas_idkelas', $kelasId);
    }

    public function scopeForTanggal($query, $tanggal)
    {
        return $query->where('tanggal', $tanggal);
    }

    public function scopeForSiswa($query, $siswaId)
    {
        return $query->where('siswa_idsiswa', $siswaId);
    }

    public function scopeForSemester($query, $semesterId)
    {
        return $query->where('semester_idsemester', $semesterId);
    }

    public function scopeForTahunajaran($query, $tahunAjaranId)
    {
        return $query->where('tahunajaran_idtahunajaran', $tahunAjaranId);
    }

    public function scopeBetweenTanggal($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }
}
