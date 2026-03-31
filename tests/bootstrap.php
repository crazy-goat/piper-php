<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Ensure test fixtures directory exists
$fixturesModels = __DIR__ . '/fixtures/models';
if (!is_dir($fixturesModels)) {
    mkdir($fixturesModels, 0755, true);
}
