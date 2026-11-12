<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $primaryKey = 'idsiswa';

    protected $fillable = [
        'idsiswa',
        'user_iduser',
        'nisn',
        'nis',
        'nik',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'telepon_siswa',
        'email',
        'nama_ayah',
        'telepon_ayah',
        'nama_ibu',
        'telepon_ibu',
        'nama_wali',
        'telepon_wali',
        'telepon_ortu',
        'tahun_masuk',
        'asal_sekolah',
        'foto',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idsiswa', 'like', "%{$value}%")
            ->orWhere('nisn', 'like', "%{$value}%")
            ->orWhere('nis', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('status', 'like', "%{$value}%");
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_iduser', 'id');
    }

    public function kelassiswa()
    {
        return $this->hasMany(Kelassiswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function nilaiSiswa()
    {
        return $this->hasMany(NilaiSiswa::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function walimurid()
    {
        return $this->hasMany(Walimurid::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function nilaiAkhir()
    {
        return $this->hasMany(NilaiAkhir::class, 'siswa_idsiswa', 'idsiswa');
    }

    public function catatanRapor()
    {
        return $this->hasMany(CatatanRapor::class, 'siswa_idsiswa', 'idsiswa');
    }
}
