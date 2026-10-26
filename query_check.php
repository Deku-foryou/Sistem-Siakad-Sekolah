<?php
// Minimal Laravel bootstrap for DB queries
require __DIR__.'/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(__DIR__);

$app->singleton('db', function ($app) {
    $config = [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'database' => 'siakad',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ];
    $connection = new \Illuminate\Database\Capsule\Manager;
    $connection->addConnection($config);
    $connection->setAsGlobal();
    $connection->booting(function () {
        // Configure Eloqu 
    });
    return $connection;
});

$capsule = Illuminate\Database\Capsule\Manager::table('users')->count();
echo "Users: $capsule\n";

$capsule2 = Illuminate\Database\Capsule\Manager::table('siswa')->count();
echo "Siswa: $capsule2\n";

$capsule3 = Illuminate\Database\Capsule\Manager::table('guru')->count();
echo "Guru: $capsule3\n";

$capsule4 = Illuminate\Database\Capsule\Manager::table('kelassiswa')->count();
echo "Kelassiswa: $capsule4\n";

$capsule5 = Illuminate\Database\Capsule\Manager::table('rolecat')->count();
echo "Rolecat: $capsule5\n";

$capsule6 = Illuminate\Database\Capsule\Manager::table('usermenu')->count();
echo "Usermenu: $capsule6\n";

// Group by level
echo "Users by level:\n";
$levels = Illuminate\Database\Capsule\Manager::table('users')
    ->select('level')
    ->groupBy('level')
    ->get();
foreach ($levels as $row) {
    echo "  {$row->level}: {$row->total}\n";
}