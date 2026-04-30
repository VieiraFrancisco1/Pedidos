<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use App\Tests\TestCase;
use App\Tests\PedidoTest;
use App\Tests\PatternsExampleTest;
use App\Tests\IntegrationTest;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$tests = [
    new PedidoTest(),
    new PatternsExampleTest(),
    new IntegrationTest(),
];

$totalFailures = 0;

foreach ($tests as $test) {
    $failures = $test->run();
    $test->report();
    $totalFailures += $failures;
}

echo "\n";
if ($totalFailures === 0) {
    echo "✓ Todos os testes passaram!\n\n";
} else {
    echo "✗ {$totalFailures} teste(s) falharam.\n\n";
}

exit($totalFailures > 0 ? 1 : 0);
