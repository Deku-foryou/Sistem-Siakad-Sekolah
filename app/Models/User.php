<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'level',
        'status_akun',
        'wajib_ganti_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'wajib_ganti_password' => 'boolean',
        'dinonaktifkan_pada' => 'datetime',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'like', "%{$value}%")
            ->orWhere('username', 'like', "%{$value}%")
            ->orWhere('email', 'like', "%{$value}%");
    }

    public function model_has_roles()
    {
        return $this->hasMany(Model_has_roles::class);
    }

    public function usermenu()
    {
        return $this->hasMany(Usermenu::class, 'user_iduser');
    }

    // Desain final 2B.7 — fondasi Portal Wali Murid (additive)
    public function walimuridRelations()
    {
        return $this->hasMany(Walimurid::class, 'user_iduser', 'id');
    }

    public function walimuridYangDiajukan()
    {
        return $this->hasMany(Walimurid::class, 'pengaju_id', 'id');
    }

    public function keputusanWalimurid()
    {
        return $this->hasMany(Walimurid::class, 'verifikator_id', 'id');
    }

    public function permintaanPutus()
    {
        return $this->hasMany(WalimuridPermintaanPutus::class, 'diminta_oleh', 'id');
    }

    public function historiesAkunWali()
    {
        return $this->hasMany(WaliakunHistory::class, 'user_iduser', 'id');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'user_iduser', 'id');
    }

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'user_iduser', 'id');
    }

    public function finalisasiRapor()
    {
        return $this->hasMany(FinalisasiRapor::class, 'difinalisasi_oleh', 'id');
    }

    public function nilaiPerubahanLog()
    {
        return $this->hasMany(NilaiPerubahanLog::class, 'diubah_oleh', 'id');
    }
}
