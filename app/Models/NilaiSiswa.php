<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiSiswa extends Model
{
    protected $table = 'nilai_siswa';

    protected $primaryKey = 'idnilai_siswa';

    protected $fillable = [
        'semester_idsemester',
        'kelas_idkelas',
        'siswa_idsiswa',
        'komponen_nilai_idkomponen_nilai',
        'nilai',
        'guru_idguru',
    ];

    protected $casts = [
        'nilai' => 'decimal:2',
    ];

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

    public function komponenNilai()
    {
        return $this->belongsTo(KomponenNilai::class, 'komponen_nilai_idkomponen_nilai', 'idkomponen_nilai');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_idguru', 'idguru');
    }

    public function perubahanLog()
    {
        return $this->hasMany(NilaiPerubahanLog::class, 'nilai_siswa_idnilai_siswa', 'idnilai_siswa');
    }
}
