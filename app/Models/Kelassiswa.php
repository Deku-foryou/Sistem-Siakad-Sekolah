<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelassiswa extends Model
{
    use HasFactory;

    protected $table = 'kelassiswa';

    protected $primaryKey = 'idkelassiswa';

    protected $fillable = [
        'idkelassiswa',
        'kelas_idkelas',
        'siswa_idsiswa',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idkelassiswa', 'like', "%{$value}%");
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_idkelas', 'idkelas');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_idsiswa', 'idsiswa');
    }
}
