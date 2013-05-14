<?php

namespace Purekid\Mongodm\Test\Model;

class User extends \Purekid\Mongodm\Model 
{

	static $collection = "user";
 	public static $config = 'test';
	
	protected static $attrs = array(
			
		'book_fav' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'reference'),
		'books' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'references'),
		'age' => array('default'=>16,'type'=>'integer'),
		'money' => array('default'=>20.0,'type'=>'double'),
		'hobbies' => array('default'=>array('love'),'type'=>'array'),
		'family'=>array('type'=>'object')
			
	);

	protected function __init(){
		if(! $this->init_data) $this->init_data = "init";
	}
	
	protected function __preSave(){
		if(! $this->pre_save_data) $this->pre_save_data = "ohohoh";
	}
	
}