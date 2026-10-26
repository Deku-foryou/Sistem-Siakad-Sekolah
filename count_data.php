<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Users count: " . $kernel->call(function () {
    return App\Models\User::count();
}) . "\n";

echo "Siswa count: " . $kernel->call(function () {
    return App\Models\Siswa::count();
}) . "\n";

echo "Guru count: " . $kernel->call(function () {
    return App\Models\Guru::count();
}) . "\n";

echo "Kelassiswa count: " . $kernel->call(function () {
    return App\Models\Kelassiswa::count();
}) . "\n";

echo "Rolecat count: " . $kernel->call(function () {
    return App\Models\Rolecat::count();
}) . "\n";

echo "Usermenu count: " . $kernel->call(function () {
    return App\Models\Usermenu::count();
}) . "\n";

// Group by level
echo "Users by level:\n";
$result = $kernel->call(function () {
    return App\Models\User::select('level', \DB::raw('count(*) as total'))
        ->groupBy('level')
        ->get()
        ->toArray();
});
print_r($result);

// Show idsiswa, nisn, nama (max 60 rows)
echo "\nSiswa data (max 60 rows):\n";
$siswa = $kernel->call(function () {
    return App\Models\Siswa::take(60)->get(['idsiswa', 'nisn', 'nama'])->toArray();
});
print_r($siswa);