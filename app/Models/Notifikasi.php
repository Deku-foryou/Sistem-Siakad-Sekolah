<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    public const JENIS_PENGUMUMAN = 'pengumuman';

    public const JENIS_RAPOR = 'rapor';

    public const JENIS_KOREKSI = 'koreksi';

    public const JENIS_UMUM = 'umum';

    public const JENIS_KEUANGAN = 'keuangan';

    public const JENIS_PENGINGAT_TAGIHAN = 'pengingat_tagihan';

    public const JENIS_LIST = [
        self::JENIS_PENGUMUMAN,
        self::JENIS_RAPOR,
        self::JENIS_KOREKSI,
        self::JENIS_UMUM,
        self::JENIS_KEUANGAN,
        self::JENIS_PENGINGAT_TAGIHAN,
    ];

    public const PRIORITAS = ['rendah', 'sedang', 'tinggi'];

    public const PRIORITAS_SEDANG = 'sedang';

    protected $table = 'notifikasi';

    protected $primaryKey = 'idnotifikasi';

    protected $fillable = [
        'judul',
        'isi',
        'jenis',
        'prioritas',
        'sumber_tabel',
        'sumber_id',
        'link',
        'dibuat_oleh',
        'waktu_dibuat',
    ];

    protected $casts = [
        'waktu_dibuat' => 'datetime',
    ];

    public function penerima()
    {
        return $this->hasMany(NotifikasiPenerima::class, 'notifikasi_idnotifikasi', 'idnotifikasi');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }
}
