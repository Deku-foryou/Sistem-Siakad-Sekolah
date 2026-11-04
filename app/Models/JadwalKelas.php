<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalKelas extends Model
{
    protected $table = 'jadwalkelas';

    protected $primaryKey = 'idjadwalkelas';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'semester_idsemester',
        'jamhari_idjamhari',
        'mapel_idmapel',
        'guru_idguru',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function jamhari()
    {
        return $this->belongsTo(Jamhari::class, 'jamhari_idjamhari', 'idjamhari');
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
