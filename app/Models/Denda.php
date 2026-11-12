<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denda extends Model
{
    protected $table = 'denda';

    protected $primaryKey = 'iddenda';

    public $timestamps = false;

    protected $fillable = [
        'tagihan_idtagihan',
        'metode',
        'nilai',
        'nominal_denda',
        'status',
        'dibebaskan_pada',
        'dibebaskan_oleh',
        'alasan_dibebaskan',
    ];

    protected $casts = [
        'nilai' => 'integer',
        'nominal_denda' => 'integer',
        'diberikan_pada' => 'datetime',
        'dibebaskan_pada' => 'datetime',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function pembebas()
    {
        return $this->belongsTo(User::class, 'dibebaskan_oleh', 'id');
    }
}
