<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $primaryKey = 'idkelas';

    protected $fillable = [
        'idkelas',
        'tahunajaran_idtahunajaran',
        'nama',
        'tingkat',
        'jenis',
        'rombel',
        'guru_idguru',
        'jurusan_id',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idkelas', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('tingkat', 'like', "%{$value}%")
            ->orWhere('jenis', 'like', "%{$value}%");
    }

    public function tahunajaran()
    {
        return $this->belongsTo(Tahunajaran::class, 'tahunajaran_idtahunajaran', 'idtahunajaran');
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id', 'idjurusan');
    }

    public function wali()
    {
        return $this->belongsTo(Guru::class, 'guru_idguru', 'idguru');
    }

    public function kelassiswa()
    {
        return $this->hasMany(Kelassiswa::class, 'kelas_idkelas', 'idkelas');
    }

    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'kelassiswa', 'kelas_idkelas', 'siswa_idsiswa');
    }
}
