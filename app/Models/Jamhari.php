<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jamhari extends Model
{
    protected $table = 'jamhari';
    protected $primaryKey = 'idjamhari';
    protected $fillable = ['hari', 'kelas_id', 'jam_ke', 'jenis', 'mulai', 'selesai', 'keterangan'];

    public function scopeHari($query, $hari)
    {
        return $query->where('hari', $hari);
    }

    public function scopeUrut($query)
    {
        return $query->orderBy('mulai');
    }
}
