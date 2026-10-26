<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Docscat extends Model
{
   use HasFactory;

   protected $table = 'docscat';
   protected $primaryKey = 'iddocscat';
   protected $fillable = [
      'iddocscat',
      'docscat',
      'urut',
   ];
   //aktifkan jika id tidak increment
// public $keyType = 'string';
// public $incrementing = false;

   // public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('iddocscat', 'like', "%{$value}%")
         ->orWhere('docscat', 'like', "%{$value}%")
         ->orWhere('urut', 'like', "%{$value}%")
      ;
   }


   /*
   public function scopeFilterDocscat($query, $value){
     if (!empty($value)) {$query->where('NAMA_FILTER_RELASI', $value);}
   }

   */
   /*
   public function scopeRangeTanggal($query, $value1, $value2)
   {
      if ($value1 && $value2) {
          $query->whereBetween('tanggal', [$value1, $value2]);
      }
   }
   */


   //contoh relasi untu menampilkan tabel child atau detail 

   public function docs()
   {
      return $this->hasMany(Docs::class, 'docscat_iddocscat');
   }


   //contoh relasi untu mengambil data parent
/*
   public function nama_table_parent()
   {
   return $this->belongsTo(nama_model_parent::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

}
