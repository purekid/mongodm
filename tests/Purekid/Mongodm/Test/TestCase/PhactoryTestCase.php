<?php

namespace Purekid\Mongodm\Test\TestCase;

use Phactory\Mongo\Phactory;
use Purekid\Mongodm\ConnectionManager;

/**
 * Test Case Base Class for using Phactory *
 */
abstract class PhactoryTestCase extends \PHPUnit_Framework_TestCase
{
  protected static $db;
  protected static $phactory;

  public static function setUpBeforeClass()
  {
    ConnectionManager::setConfigBlock('testing', array(
      'connection' => array(
        'hostnames' => 'localhost',
        'database'  => 'test_db'
      )
    ));

    self::$db = ConnectionManager::instance('testing');
    self::$db->connect();

    if (!self::$phactory) {
      if (! (self::$db->getMongoDB() instanceof \MongoDB)) {
        throw new \Exception('Could not connect to ConnectionManager');
      }
      
      self::$phactory = new Phactory(self::$db->getMongoDB());
      self::$phactory->reset();
    }

    //set up Phactory db connection
    self::$phactory->reset();
  }

  public static function tearDownAfterClass()
  {
    foreach (self::$db->getMongoDB()->getCollectionNames() as $collection) {
      self::$db->getMongoDB()->$collection->drop();
    }
  }

  protected function setUp()
  {
  }

  protected function tearDown()
  {
    self::$phactory->recall();
  }
}
