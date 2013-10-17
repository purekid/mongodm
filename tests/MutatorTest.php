<?php

use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;
use Purekid\Mongodm\Collection;

class MutatorTest extends PHPUnit_Framework_TestCase {
  
  public function testMutate(){
    
    $john = new User();
    $john->age = 18;
    $john->save();
    
    $user = User::one();
    $id = $user->getId();
    $age = $user->age;

    $user->mutate(array('$inc' => array('age' => 1)));
    
    $user = User::id($id);
    $this->assertEquals($age + 1, $user->age);
    
  }
  
  public function testMutateFail(){
    
    $user = User::one();
    $id = $user->getId();
    $age = $user->age;

    $fail = $user->mutate(array('$set' => array('ages' => array('$inc' => 1))));
    
    $this->assertFalse($fail);
    
  }
  
}
