<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiAkhir extends Model
{
    protected $table = 'nilai_akhir';

    protected $primaryKey = 'idnilai_akhir';

    protected $fillable = [
        'semester_idsemester',
        'kelas_idkelas',
        'mapel_idmapel',
        'siswa_idsiswa',
        'nilai_akhir',
        'nilai_kktp',
        'dihitung_pada',
    ];

    protected $casts = [
        'nilai_akhir' => 'decimal:2',
        'nilai_kktp' => 'decimal:2',
        'dihitung_pada' => 'datetime',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_idmapel', 'idmapel');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function perubahanLog()
    {
        return $this->hasMany(NilaiPerubahanLog::class, 'nilai_akhir_idnilai_akhir', 'idnilai_akhir');
    }
}
