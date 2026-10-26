<?php

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$exitCode = $kernel->handle(/* $options */);

// Let's just bootstrap and run queries
$app->make('Illuminate\Contracts\Console\Kernel');

echo "Testing connection...\n";

$db = new class {
    public function query($sql) {
        // Can't easily do this without full Laravel bootstrap
    }
};

// Let's try using artizan tinker
echo "Trying artisan approach...\n";