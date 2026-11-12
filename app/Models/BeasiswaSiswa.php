<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeasiswaSiswa extends Model
{
    protected $table = 'beasiswa_siswa';

    protected $primaryKey = 'idbeasiswa_siswa';

    protected $fillable = [
        'beasiswa_idbeasiswa',
        'siswa_idsiswa',
        'berlaku_dari',
        'berlaku_sampai',
        'alasan',
        'status',
        'dibuat_oleh',
    ];

    protected $casts = [
        'berlaku_dari' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function beasiswa()
    {
        return $this->belongsTo(Beasiswa::class, 'beasiswa_idbeasiswa', 'idbeasiswa');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function kreator()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id');
    }
}
