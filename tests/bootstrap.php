<?php

if (!file_exists($file = __DIR__.'/../../../autoload.php') && !file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require_once $file;

$map = array(
		'Purekid\Mongodm\Test\Model\Base'=> __DIR__."/Models/Base.php",
		'Purekid\Mongodm\Test\Model\User'=> __DIR__."/Models/User.php",
		'Purekid\Mongodm\Test\Model\Book'=> __DIR__."/Models/Book.php",
		'Purekid\Mongodm\Test\Model\Student'=> __DIR__."/Models/Student.php",
		'Purekid\Mongodm\Test\Model\Pupil'=> __DIR__."/Models/Pupil.php"
		);
$loader->addClassMap($map);