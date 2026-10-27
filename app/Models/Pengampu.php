<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengampu extends Model
{
    protected $table = 'pengampu';

    protected $primaryKey = 'idpengampu';

    protected $fillable = ['semester_idsemester', 'kelas_idkelas', 'mapel_idmapel', 'guru_idguru'];

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

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'pengampu_idpengampu', 'idpengampu');
    }
}
