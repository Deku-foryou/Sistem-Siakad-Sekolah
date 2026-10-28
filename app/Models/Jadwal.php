<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $primaryKey = 'idjadwal';

    protected $fillable = ['pengampu_idpengampu', 'hari', 'jam_ke', 'status'];

    public function pengampu()
    {
        return $this->belongsTo(Pengampu::class, 'pengampu_idpengampu', 'idpengampu');
    }
}
