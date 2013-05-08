<?php

namespace Purekid\Mongodm\Test\Model;

class User extends \Purekid\Mongodm\Model 
{

	static $collection = "user";
 	public static $config = 'test';
	
	public $references = array(
			
		'book_fav' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'one'),
		'books' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'many'),
			
	);

}