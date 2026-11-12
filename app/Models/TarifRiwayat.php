<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifRiwayat extends Model
{
    protected $table = 'tarif_riwayat';

    protected $primaryKey = 'idtarif_riwayat';

    public $timestamps = false;

    protected $fillable = [
        'tarif_idtarif',
        'aksi',
        'nominal_lama',
        'nominal_baru',
        'nama_lama',
        'nama_baru',
        'berlaku_dari_lama',
        'berlaku_dari_baru',
        'berlaku_sampai_lama',
        'berlaku_sampai_baru',
        'status_lama',
        'status_baru',
        'alasan',
        'oleh',
    ];

    protected $casts = [
        'berlaku_dari_lama' => 'date',
        'berlaku_dari_baru' => 'date',
        'berlaku_sampai_lama' => 'date',
        'berlaku_sampai_baru' => 'date',
    ];

    public function tarif()
    {
        return $this->belongsTo(Tarif::class, 'tarif_idtarif', 'idtarif');
    }

    public function pelaku()
    {
        return $this->belongsTo(User::class, 'oleh', 'id');
    }
}
