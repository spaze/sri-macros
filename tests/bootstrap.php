<?php
declare(strict_types = 1);

use Tester\Environment;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

@mkdir(__DIR__ . '/../temp/tests'); // intentionally @ - the dir might exist already
