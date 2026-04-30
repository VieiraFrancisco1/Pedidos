<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use App\Tests\PedidoTest;

$testRunner = new PedidoTest();
$failures = $testRunner->run();
$testRunner->report();

exit($failures > 0 ? 1 : 0);
