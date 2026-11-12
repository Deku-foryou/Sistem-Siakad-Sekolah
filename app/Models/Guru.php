<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'guru';

    protected $primaryKey = 'idguru';

    protected $fillable = [
        'idguru',
        'user_iduser',
        'nip',
        'kode_guru',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'telepon',
        'alamat',
        'foto',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idguru', 'like', "%{$value}%")
            ->orWhere('nip', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('status', 'like', "%{$value}%");
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'guru_idguru', 'idguru');
    }

    public function kelassiswa()
    {
        return $this->hasManyThrough(Kelassiswa::class, Kelas::class, 'guru_idguru', 'kelas_idkelas', 'idguru', 'idkelas');
    }

    public function mapel()
    {
        return $this->belongsToMany(Mapel::class, 'gurumapel', 'guru_idguru', 'mapel_idmapel', 'idguru', 'idmapel');
    }

    public function jadwalkelas()
    {
        return $this->hasMany(JadwalKelas::class, 'guru_idguru', 'idguru');
    }

    public function penugasanMengajar()
    {
        return $this->hasMany(PenugasanMengajar::class, 'guru_idguru', 'idguru');
    }

    public function nilaiSiswa()
    {
        return $this->hasMany(NilaiSiswa::class, 'guru_idguru', 'idguru');
    }

    public function catatanRapor()
    {
        return $this->hasMany(CatatanRapor::class, 'guru_idguru', 'idguru');
    }

    public function finalisasiRaporSebagaiWali()
    {
        return $this->hasMany(FinalisasiRapor::class, 'wali_kelas_idguru', 'idguru');
    }
}
