<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Desain final §5.3 — permintaan pemutusan hubungan oleh wali.
 * Keputusan (setujui/tolak) adalah milik pengelola berwenang (T1c).
 */
class WalimuridPermintaanPutus extends Model
{
    use HasFactory;

    protected $table = 'walimurid_permintaan_putus';

    protected $primaryKey = 'idpermintaan_putus';

    protected $fillable = [
        'walimurid_id',
        'diminta_oleh',
        'alasan',
        'status_permintaan',
        'diputuskan_oleh',
        'diputuskan_pada',
        'catatan_keputusan',
    ];

    protected $casts = [
        'diputuskan_pada' => 'datetime',
    ];

    public const STATUS_PERMINTAAN = ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'];

    public function relasi()
    {
        return $this->belongsTo(Walimurid::class, 'walimurid_id', 'idwalimurid');
    }

    public function peminta()
    {
        return $this->belongsTo(User::class, 'diminta_oleh', 'id');
    }

    public function pemutus()
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh', 'id');
    }
}
