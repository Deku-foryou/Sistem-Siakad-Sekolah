<?php
// Manual count check - this is what we would run
// Using tinker or direct SQL
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Count before any test operations
$userCount = \App\Models\User::count();
$siswaCount = \App\Models\Siswa::count();
$guruCount = \App\Models\Guru::count();

echo "=== COUNT BEFORE TESTS ===\n";
echo "Users: $userCount\n";
echo "Siswa: $siswaCount\n";
echo "Guru: $guruCount\n";

// Now let's check Rolecat counts for PaketDefaultSeeder
$rolecatSiswa = \App\Models\Rolecat::where('rolecat', 'Siswa')->count();
$rolecatGuru = \App\Models\Rolecat::where('rolecat', 'Guru')->count();

echo "\n=== ROLECAT BEFORE PakeetDefaultSeeder ===\n";
echo "Rolecat Siswa: $rolecatSiswa\n";
echo "Rolecat Guru: $rolecatGuru\n";
?>