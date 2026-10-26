<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "siswa: " . config('siakad.paket_default.siswa') . PHP_EOL;
echo "guru: " . config('siakad.paket_default.guru') . PHP_EOL;