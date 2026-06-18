<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('role', 'hrd')->get();

foreach ($users as $u) {
    echo $u->nik . ' | ' . $u->full_name . PHP_EOL;
}
