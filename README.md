mongodm
=======

a PHP MongoDb ODM 


======


base Model
------

	<?php

	class User extends \Mongodm\Model{

		static $collection = "user";
		
		public $references = array(			
			'book_fav' => array('model'=>'Mongodm\Test\Model\Book','type'=>'one'),
			'books' => array('model'=>'Mongodm\Test\Model\Book','type'=>'many'),			
		);

	}

create
------

	$user = new User();
	$user->name = "Michael";
	$user->save();

create with data
------
	
	$user_other = new User( array('name'=>"John") );
	$user_other->save();

load one record
------

	$user = User::one( array('name'=>"michael" ) );

	//[load one record by MongoId]
	$id = "517c850641da6da0ab000004";
	$id = new \MongoId('517c850641da6da0ab000004'); // hah,both ok!
	$user = User::id( $id );

load all records
------
	$users = User::all();

relationship 1:1
------

	$book = new Book();
	$book->name = "My Love";
	$book->price = 15;
	$book->save();

	// !!!remember you must save book before!!!
	$user->book_fav = $book;
	$user->save();

	// now you can do this
	$user = User::one( array('name'=>"michael" ) );
	echo $user->book_fav->name;


relationship 1:x
------
