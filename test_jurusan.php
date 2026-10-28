<?php

require 'vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$j = $kernel->call('DB::table', [
    'table' => 'jurusan',
    'values' => ['kode' => 'MIPA', 'nama' => 'Matematika dan Ilmu Pengetahuan Alam', 'aktif' => 1],
]);

echo "Jurusan inserted, ID: " . $j . "\n";

// Test reading back
$rows = DB::table('jurusan')->where('kode', 'MIPA')->get();
echo "Jurusan found: " . $rows[0]->nama . "\n";

// Test creating via model
$jurusan = new App\Models\Jurusan(['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan', 'aktif' => true]);
$jurusan->save();
echo "TKJ jurusan created\n";

// Test with the buat method if exists
$result = App\Models\Jurusan::buat(['kode' => 'BIND', 'nama' => 'Bahasa Indonesia']);
if ($result['success']) {
    echo "Buat method success: " . $result['jurusan']->kode . " - " . $result['jurusan']->nama . "\n";
} else {
    echo "Buat method errors: " . implode(', ', array_values($result['errors'] ?? [])) . "\n";
}

// Test duplicate check
$result2 = App\Models\Jurusan::buat(['kode' => 'MIPA', 'nama' => 'Duplicate Test']);
if (!$result2['success']) {
    echo "Duplicate check works: " . implode(', ', array_values($result2['errors'] ?? [])) . "\n";
}

// Test deleting when not in use
$countBefore = App\Models\Kelas::whereNull('jurusan_id')->count();
echo "Kelas without jurusan before delete: " . $countBefore . "\n";

// Test that we can assign jurusan to kelas
$jurusanMipa = App\Models\Jurusan::where('kode', 'MIPA')->first();
$kelasMipa = App\Models\Kelas::find(31);
$kelasMipa->jurusan_id = $jurusanMipa->idjurusan;
$kelasMipa->save();
echo "Jurusan assigned to kelas Mipa-1\n";

$kelasAfter = App\Models\Kelas::find(31);
echo "Kelas jurusan_id after assign: " . $kelasAfter->jurusan_id . "\n";
echo "Kelas jurusan relationship: " . ($kelasAfter->jurusan ? $kelasAfter->jurusan->nama : 'null') . "\n";

// Verify count after assignment
$countAfter = App\Models\Kelas::whereNotNull('jurusan_id')->count();
echo "Kelas dengan jurusan after assign: " . $countAfter . "\n";
echo "Kelas tanpa jurusan after assign: " . App\Models\Kelas::whereNull('jurusan_id')->count() . "\n";