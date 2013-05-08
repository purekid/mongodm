<?php

if (!file_exists($file = __DIR__.'/../../../autoload.php')) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require_once $file;

$loader->add('Purekid\Mongodm\Test\Model', __DIR__."/Models");

