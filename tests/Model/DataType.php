<?php

namespace Purekid\Mongodm\Test\Model;

class DataType extends Base
{

  static $collection = "dataType";

  protected static $attrs = array(
    \Purekid\Mongodm\Model::DATA_TYPE_ARRAY      => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_ARRAY,      'default'=>array(1,2,3)),
    \Purekid\Mongodm\Model::DATA_TYPE_BOOLEAN    => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_BOOLEAN,    'default'=>true),
    \Purekid\Mongodm\Model::DATA_TYPE_DATE       => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_DATE,       'default'=>"now"),
    \Purekid\Mongodm\Model::DATA_TYPE_DOUBLE     => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_DOUBLE,     'default'=>1.2345),
    \Purekid\Mongodm\Model::DATA_TYPE_EMBED      => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_EMBED,      'model'=>'Purekid\Mongodm\Test\Model\Embed'),
    \Purekid\Mongodm\Model::DATA_TYPE_EMBEDS     => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_EMBEDS,     'model'=>'Purekid\Mongodm\Test\Model\Embed'),
    \Purekid\Mongodm\Model::DATA_TYPE_INT        => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_INT,        'default'=>123),
    \Purekid\Mongodm\Model::DATA_TYPE_INTEGER    => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_INTEGER,    'default'=>456),
    \Purekid\Mongodm\Model::DATA_TYPE_MIXED      => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_MIXED,      'default'=>array(1,'a',array('b'=>'B'))),
    \Purekid\Mongodm\Model::DATA_TYPE_REFERENCE  => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_REFERENCE,  'model'=>'Purekid\Mongodm\Test\Model\Refernece'),
    \Purekid\Mongodm\Model::DATA_TYPE_REFERENCES => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_REFERENCES, 'model'=>'Purekid\Mongodm\Test\Model\Reference'),
    \Purekid\Mongodm\Model::DATA_TYPE_STRING     => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_STRING,     'default'=>'string'),
    \Purekid\Mongodm\Model::DATA_TYPE_TIMESTAMP  => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_TIMESTAMP,  'default'=>0),
    \Purekid\Mongodm\Model::DATA_TYPE_OBJECT     => array('type'=>\Purekid\Mongodm\Model::DATA_TYPE_OBJECT,     'default'=>array('a' => 'A')),
    'invalid'                                    => array('type'=>'invalid')
  );
  
}