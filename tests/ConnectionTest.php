<?php

namespace Purekid\Mongodm\Test;

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
        MongoDB::setConfigBlock($env, $options);
        $i = MongoDB::instance($env);
        $instanceOptions = $i->config($env);
        $this->assertEquals(
            $options['connection']['options'],
            $instanceOptions['connection']['options']
        );
    }
}
