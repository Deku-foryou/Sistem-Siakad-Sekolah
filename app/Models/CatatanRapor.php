<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanRapor extends Model
{
    protected $table = 'catatan_rapor';

    protected $primaryKey = 'idcatatan_rapor';

    protected $fillable = [
        'semester_idsemester',
        'siswa_idsiswa',
        'catatan',
        'guru_idguru',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_idguru', 'idguru');
    }
}
