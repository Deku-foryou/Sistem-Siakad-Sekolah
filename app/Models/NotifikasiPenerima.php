<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiPenerima extends Model
{
    protected $table = 'notifikasi_penerima';

    protected $primaryKey = 'idnotifikasi_penerima';

    protected $fillable = [
        'notifikasi_idnotifikasi',
        'user_iduser',
        'dibaca_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
    ];

    public function notifikasi()
    {
        return $this->belongsTo(Notifikasi::class, 'notifikasi_idnotifikasi', 'idnotifikasi');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }
}
