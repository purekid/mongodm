<?php

use Purekid\Mongodm\MongoDB;

class GridFsTest extends PHPUnit_Framework_TestCase
{
    public function getGridFSPrefixes()
    {
        return array(
            array(null),
            array('files-')
        );
    }

    /**
    * @dataProvider getGridFSPrefixes
    */
    public function testGetGridFs($prefix)
    {
        $mongo_db = MongoDB::instance();
        $grid_fs = $mongo_db->gridFs($prefix);
        $this->assertInstanceOf('MongoGridFS', $grid_fs);
    }
}
