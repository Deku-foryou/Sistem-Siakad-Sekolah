<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalisasiRapor extends Model
{
    public const STATUS_DRAF = 'draf';

    public const STATUS_FINAL = 'final';

    public const STATUS_VALID = [self::STATUS_DRAF, self::STATUS_FINAL];

    protected $table = 'finalisasi_rapor';

    protected $primaryKey = 'idfinalisasi_rapor';

    protected $fillable = [
        'semester_idsemester',
        'kelas_idkelas',
        'status',
        'wali_kelas_idguru',
        'difinalisasi_oleh',
        'difinalisasi_pada',
    ];

    protected $casts = [
        'difinalisasi_pada' => 'datetime',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_idguru', 'idguru');
    }

    public function difinalisasiOleh()
    {
        return $this->belongsTo(User::class, 'difinalisasi_oleh', 'id');
    }
}
