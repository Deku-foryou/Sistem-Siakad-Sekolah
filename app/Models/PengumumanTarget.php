<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengumumanTarget extends Model
{
    protected $table = 'pengumuman_target';

    protected $primaryKey = 'idpengumuman_target';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'pengumuman_idpengumuman',
        'jurusan_idjurusan',
        'kelas_idkelas',
        'siswa_idsiswa',
    ];

    public const JENIS = ['jurusan', 'kelas', 'siswa'];

    public function pengumuman()
    {
        return $this->belongsTo(Pengumuman::class, 'pengumuman_idpengumuman', 'idpengumuman');
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_idjurusan', 'idjurusan');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function scopeSearch($query, $value)
    {
        $query->where('idpengumuman_target', 'like', "%{$value}%");
    }
}
