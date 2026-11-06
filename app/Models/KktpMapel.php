<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KktpMapel extends Model
{
    protected $table = 'kktp_mapel';

    protected $primaryKey = 'idkktp_mapel';

    protected $fillable = [
        'semester_idsemester',
        'mapel_idmapel',
        'nilai_minimal',
        'deskripsi',
    ];

    protected $casts = [
        'nilai_minimal' => 'decimal:2',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_idmapel', 'idmapel');
    }
}
