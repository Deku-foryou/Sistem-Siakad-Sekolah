<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapel';

    protected $primaryKey = 'idmapel';

    protected $fillable = [
        'idmapel',
        'kode',
        'nama',
        'kelompok',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idmapel', 'like', "%{$value}%")
            ->orWhere('kode', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('kelompok', 'like', "%{$value}%");
    }

    public function guru()
    {
        return $this->belongsToMany(Guru::class, 'gurumapel', 'mapel_idmapel', 'guru_idguru', 'idmapel', 'idguru');
    }

    public function jadwalkelas()
    {
        return $this->hasMany(JadwalKelas::class, 'mapel_idmapel', 'idmapel');
    }

    public function penugasanMengajar()
    {
        return $this->hasMany(PenugasanMengajar::class, 'mapel_idmapel', 'idmapel');
    }

    public function komponenNilai()
    {
        return $this->hasMany(KomponenNilai::class, 'mapel_idmapel', 'idmapel');
    }

    public function kktpMapel()
    {
        return $this->hasMany(KktpMapel::class, 'mapel_idmapel', 'idmapel');
    }

    public function nilaiAkhir()
    {
        return $this->hasMany(NilaiAkhir::class, 'mapel_idmapel', 'idmapel');
    }
}
