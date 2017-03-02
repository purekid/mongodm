<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\ConnectionManager;
use Purekid\Mongodm\MongoDB;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testOptions()
    {
        $env = 'myenv';
        $options = array(
            'connection' => array(
                'options'  => array('w' => 2)
            )
        );

        ConnectionManager::setConfigBlock($env, $options);
        $i = ConnectionManager::instance($env);
        $instanceOptions = $i->config($env);
        $this->assertEquals(
            $options['connection']['options'],
            $instanceOptions['connection']['options']
        );
    }
}
