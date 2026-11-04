<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengumumanBaca extends Model
{
    protected $table = 'pengumuman_baca';

    protected $primaryKey = 'idpengumuman_baca';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'pengumuman_idpengumuman',
        'siswa_idsiswa',
        'dibaca_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
    ];

    public function pengumuman()
    {
        return $this->belongsTo(Pengumuman::class, 'pengumuman_idpengumuman', 'idpengumuman');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function scopeSearch($query, $value)
    {
        $query->where('idpengumuman_baca', 'like', "%{$value}%");
    }

    public function scopeForSiswa($query, $siswaId)
    {
        return $query->where('siswa_idsiswa', $siswaId);
    }

    public function scopeForPengumuman($query, $pengumumanId)
    {
        return $query->where('pengumuman_idpengumuman', $pengumumanId);
    }

    public function scopeBelumDibaca($query)
    {
        return $query->whereNull('dibaca_pada');
    }

    public function scopeSudahDibaca($query)
    {
        return $query->whereNotNull('dibaca_pada');
    }
}
