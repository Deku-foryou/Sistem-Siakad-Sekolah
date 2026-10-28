<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publikasijadwal extends Model
{
    protected $table = 'publikasijadwal';

    protected $primaryKey = 'idpublikasijadwal';

    protected $fillable = ['nomor', 'semester_idsemester', 'kelas_idkelas', 'versi', 'status', 'published_at', 'withdrawn_at', 'published_by', 'snapshot'];

    protected $casts = ['snapshot' => 'array', 'published_at' => 'datetime', 'withdrawn_at' => 'datetime'];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_idsemester', 'idsemester');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
