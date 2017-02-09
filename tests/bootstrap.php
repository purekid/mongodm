<?php

if ( ! file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    echo "You must install the dev dependencies using:\n";
    echo "    composer install --dev\n";
    exit(1);
}

date_default_timezone_set('UTC');

$loader = require_once $file;

$map = array(
        'Purekid\Mongodm\Test\Model\Base'=> __DIR__."/Model/Base.php",
        'Purekid\Mongodm\Test\Model\User'=> __DIR__."/Model/User.php",
        'Purekid\Mongodm\Test\Model\Book'=> __DIR__."/Model/Book.php",
        'Purekid\Mongodm\Test\Model\Student'=> __DIR__."/Model/Student.php",
        'Purekid\Mongodm\Test\Model\Pupil'=> __DIR__."/Model/Pupil.php",
        'Purekid\Mongodm\Test\Model\Pet'=> __DIR__."/Model/Pet.php",
        'Purekid\Mongodm\Test\Model\DataType'=> __DIR__."/Model/DataType.php"
    );
$loader->addClassMap($map);

$loader->add('Purekid\Mongodm\Test', __DIR__);
