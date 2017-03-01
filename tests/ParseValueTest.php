<?php

namespace Purekid\Mongodm\Test;

class ParseValueTest extends \Purekid\Mongodm\Test\TestCase\PhactoryTestCase
{
  public $model;

  public function __construct()
  {
    $this->model = new \Purekid\Mongodm\Test\Model\DataType();
  }

  public function testArray()
  {
    $data = array(1,2,3);
    $actual = $this->model->parseValue('array', $data);
    $this->assertInternalType('array', $actual);

    $this->setExpectedException('\Purekid\Mongodm\Exception\InvalidDataTypeException');
    $data = 1;
    $actual = $this->model->parseValue('array', $data);
  }

  public function testInteger()
  {
    $data = 1;
    $actual = $this->model->parseValue('int', $data);
    $this->assertInternalType('integer', $actual);

    $data = 2.1;
    $actual = $this->model->parseValue('integer', $data);
    $this->assertInternalType('integer', $actual);
  }

  public function testString()
  {
    $data = 'string';
    $actual = $this->model->parseValue('string', $data);
    $this->assertInternalType('string', $actual);

    $data = 1;
    $actual = $this->model->parseValue('string', $data);
    $this->assertInternalType('string', $actual);
  }

  public function testDouble()
  {
    $data = 1.23456;
    $actual = $this->model->parseValue('double', $data);
    $this->assertInternalType('float', $actual);

    $data = '1.23456';
    $actual = $this->model->parseValue('double', $data);
    $this->assertInternalType('float', $actual);
  }

  public function testTimestamp()
  {

    $data = time();
    $actual = $this->model->parseValue('timestamp', $data);
    $this->assertInstanceOf(\MongoTimestamp::class, $actual);
    $this->assertEquals($data, $actual->sec);
//    $this->setExpectedException(\Purekid\Mongodm\Exception\InvalidDataTypeException::class);
//    $data = 'today';
//    $actual = $this->model->parseValue('timestamp', $data);

  }

  public function testDate()
  {
    $data = time();
    $actual = $this->model->parseValue('date', $data);
    $this->assertInstanceOf('\MongoDate', $actual);

    $data = 'today';
    $actual = $this->model->parseValue('date', $data);
    $this->assertInstanceOf('\MongoDate', $actual);

    $data = new \DateTime();
    $actual = $this->model->parseValue('date', $data);
    $this->assertInstanceOf('\MongoDate', $actual);

    $this->setExpectedException(\Purekid\Mongodm\Exception\InvalidDataTypeException::class);
    $data = 'a random string';
    $actual = $this->model->parseValue('date', $data);
  }

  public function testBool()
  {
    $data = true;
    $actual = $this->model->parseValue('boolean', $data);
    $this->assertInternalType('boolean', $actual);

    $data = 'false';
    $actual = $this->model->parseValue('boolean', $data);
    $this->assertInternalType('boolean', $actual);
  }

  public function testObject()
  {
    $data = new \StdClass();
    $actual = $this->model->parseValue('object', $data);
    $this->assertInternalType('object', $actual);

    $data = array('a' => 'A', 1);
    $actual = $this->model->parseValue('object', $data);
    $this->assertInternalType('object', $actual);
  }

  public function testOtherValidTypes()
  {
    $data = null;
    $actual = $this->model->parseValue('mixed', $data);
    $this->assertInternalType('null', $actual);

    $actual = $this->model->parseValue('embed', $data);
    $this->assertInternalType('null', $actual);
    $actual = $this->model->parseValue('embeds', $data);
    $this->assertInternalType('null', $actual);

    $actual = $this->model->parseValue('reference', $data);
    $this->assertInternalType('null', $actual);
    $actual = $this->model->parseValue('references', $data);
    $this->assertInternalType('null', $actual);
  }

  public function testInvalidType()
  {
    $this->setExpectedException('\Purekid\Mongodm\Exception\InvalidDataTypeException');
    $this->model->parseValue('invalid', null);
  }

}
