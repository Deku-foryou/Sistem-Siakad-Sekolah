<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Polajam extends Model
{
    protected $table = 'polajam';

    protected $primaryKey = 'idpolajam';

    protected $fillable = ['nama'];

    public function hari()
    {
        return $this->hasMany(Polajamhari::class, 'polajam_idpolajam', 'idpolajam');
    }

    public function jamPelajaran()
    {
        return $this->hasMany(Jampelajaran::class, 'polajam_idpolajam', 'idpolajam');
    }
}
