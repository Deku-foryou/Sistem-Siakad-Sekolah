<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenugasanMengajar extends Model
{
    protected $table = 'penugasan_mengajar';

    protected $primaryKey = 'idpenugasan_mengajar';

    protected $fillable = [
        'semester_idsemester',
        'kelas_idkelas',
        'mapel_idmapel',
        'guru_idguru',
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

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_idguru', 'idguru');
    }
}
