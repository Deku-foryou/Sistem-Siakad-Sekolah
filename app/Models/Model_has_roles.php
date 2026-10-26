<?php
namespace App\Models;

use App\Models\User;
use App\Models\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Model_has_roles extends Model
{
   use HasFactory;

   protected $table = 'model_has_roles';
   // protected $primaryKey = 'idmodel_has_roles';
   protected $fillable = [
      'role_id',
      //  'model_type', 
      'model_id',
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

   public function model()
   {
      return $this->belongsTo(User::class, 'model_id', 'id');
   }

   public function users()
   {
      return $this->belongsTo(User::class, 'model_id', 'id');
   }


   public function scopeSearch($query, $value)
   {
      $query->where('model_type', 'like', '%' . $value . '%')
         ->orWhere('model_id', 'like', '%' . $value . '%')
      ;
   }
}