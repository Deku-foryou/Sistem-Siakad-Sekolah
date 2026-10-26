<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Docs extends Model
{
   use HasFactory;

   protected $table = 'docs';
   protected $primaryKey = 'iddocs';
   protected $fillable = [
      'iddocs',
      'title',
      'content',
      'urut',
      'docscat_iddocscat',
   ];
   //aktifkan jika id tidak increment
// public $keyType = 'string';
// public $incrementing = false;

   // public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('iddocs', 'like', "%{$value}%")
         ->orWhere('title', 'like', "%{$value}%")
         ->orWhere('content', 'like', "%{$value}%")
         ->orWhere('urut', 'like', "%{$value}%")
         ->orWhere('docscat_iddocscat', 'like', "%{$value}%")
      ;
   }


   /*
   public function scopeFilterDocs($query, $value){
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
/*
   public function nama_table_detail()
   {
   return $this->hasMany(nama_model_child::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

   //contoh relasi untu mengambil data parent

   public function docscat()
   {
      return $this->belongsTo(Docscat::class, 'docscat_iddocscat', 'iddocscat');
   }


}
