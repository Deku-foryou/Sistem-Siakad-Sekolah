<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role_has_permissions extends Model
{
   use HasFactory;

   protected $table = 'role_has_permissions';
   // protected $primaryKey = 'idrole_has_permissions';
   protected $fillable = [
      'permission_id',
      'role_id',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   //contoh relasi untu menampilkan tabel child atau detail 
/*
   public function nama_table_detail()
   {
   return $this->hasMany(nama_model_child::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

   //contoh relasi untu mengambil data parent

   public function roles()
   {
      return $this->belongsTo(Roles::class, 'role_id', 'id');
   }

   public function permissions()
   {
      return $this->belongsTo(Permissions::class, 'permission_id', 'id');
   }

   public function scopeSearch($query, $value)
   {
      $query->where('permission_id', 'like', '%' . $value . '%')
         ->orWhere('role_id', 'like', '%' . $value . '%')
      ;
   }



}