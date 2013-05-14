<?php

use Purekid\Mongodm\Test\Model\Pupil;

class TestHooks extends PHPUnit_Framework_TestCase {

	
	public function testInit()
	{
	
		$user = new Pupil();
		$user->name = "michael";
		$user->save();
	
		$this->assertEquals("init", $user->init_data);
	
	}
	
	public function testPresave()
	{ 
		
		$user = new Pupil();
		$user->name = "michael";
		$user->save();
		
		$this->assertEquals("ohohoh", $user->pre_save_data);
		
	}
	
	
}