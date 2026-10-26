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
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'nama_ayah',
        'nama_ibu',
        'telepon_ortu',
        'tahun_masuk',
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
}
