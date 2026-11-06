<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomponenNilai extends Model
{
    protected $table = 'komponen_nilai';

    protected $primaryKey = 'idkomponen_nilai';

    protected $fillable = [
        'semester_idsemester',
        'mapel_idmapel',
        'nama',
        'bobot',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'bobot' => 'decimal:2',
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_idmapel', 'idmapel');
    }

    public function nilaiSiswa()
    {
        return $this->hasMany(NilaiSiswa::class, 'komponen_nilai_idkomponen_nilai', 'idkomponen_nilai');
    }
}
