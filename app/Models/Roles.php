<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
   use HasFactory;

   protected $table = 'roles';
   protected $primaryKey = 'id';
   protected $fillable = [
      //  'id', 
      'name',
      'guard_name',
      //  'created_at', 
      //  'updated_at', 
   ];
   // public $incrementing = false;
// public $timestamps = false;

   //contoh relasi untu menampilkan tabel child atau detail 

   public function role_has_permissions()
   {
      return $this->hasMany(Role_has_permissions::class);
   }

   public function model_has_roles()
   {
      return $this->hasMany(Model_has_roles::class);
   }


   public function scopeSearch($query, $value)
   {
      $query->where('id', 'like', "%{$value}%")
         ->orWhere('name', 'like', "%{$value}%")
         ->orWhere('guard_name', 'like', "%{$value}%")
      ;
   }



   //contoh relasi untu mengambil data parent
/*
   public function nama_table_parent()
   {
   return $this->belongsTo(nama_model_parent::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

}