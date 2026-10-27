<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jampelajaran extends Model
{
    protected $table = 'jampelajaran';

    protected $primaryKey = 'idjampelajaran';

    protected $fillable = ['polajam_idpolajam', 'jenis', 'jam_ke', 'mulai', 'selesai'];

    public function pola()
    {
        return $this->belongsTo(Polajam::class, 'polajam_idpolajam', 'idpolajam');
    }
}
