<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Polajamhari extends Model
{
    protected $table = 'polajamhari';

    protected $primaryKey = 'idpolajamhari';

    protected $fillable = ['polajam_idpolajam', 'hari'];

    public function pola()
    {
        return $this->belongsTo(Polajam::class, 'polajam_idpolajam', 'idpolajam');
    }
}
