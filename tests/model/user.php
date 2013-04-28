<?php

namespace Mongodm\Test\Model;

class User extends \Mongodm\Model{

	static $collection = "user";
	
	public $references = array(
			
		'book_fav' => array('model'=>'Mongodm\Test\Model\Book','type'=>'one'),
		'books' => array('model'=>'Mongodm\Test\Model\Book','type'=>'many'),
			
	);

}