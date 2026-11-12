<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanAdjustment extends Model
{
    protected $table = 'tagihan_adjustment';

    protected $primaryKey = 'idtagihan_adjustment';

    public $timestamps = false;

    protected $fillable = [
        'tagihan_idtagihan',
        'jenis',
        'nominal_lama',
        'nominal_baru',
        'alasan',
        'oleh',
    ];

    protected $casts = [
        'nominal_lama' => 'integer',
        'nominal_baru' => 'integer',
        'created_at' => 'datetime',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_idtagihan', 'idtagihan');
    }

    public function pelaku()
    {
        return $this->belongsTo(User::class, 'oleh', 'id');
    }
}
