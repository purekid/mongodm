Mongodm , a PHP MongoDb ORM
======= 
[![Build Status](https://secure.travis-ci.org/purekid/mongodm.png?branch=master)](http://travis-ci.org/purekid/mongodm)

Requirements
------------
- PHP 5.3 or greater
- Mongodb 1.3 or greater
- PHP Mongo extension 

Features
--------

- ORM
- Simple and flexible
- Support for references (lazy loaded)
- Support for inheritance


How to Use
----------

### Config database in   config/database.php
	<?php
		return array(
	
		   /* Configuration name */
				
			'default' => array(
				'connection' => array(
					/** hostnames, separate multiple hosts by commas **/
					'hostnames' => 'localhost',
						
					/** database to connect to **/
					'database'  => 'dbname',
						
					/** authentication **/
						
		// 			'username'  => '',
				
		// 			'password'  => '',
		
				)
			)
		);

### Define a model

	<?php

	class User extends \Mongodm\Model{

		static $collection = "user";
		
		public $references = array(			
			'book_fav' => array('model'=>'Mongodm\Test\Model\Book','type'=>'one'),
			'books' => array('model'=>'Mongodm\Test\Model\Book','type'=>'many'),			
		);

	}


### Create model instance


	$user = new User();
	$user->name = "Michael";
	$user->save();

### Create instance with data
	
	$user_other = new User( array('name'=>"John") );
	$user_other->save();

### Load one record

	$user = User::one( array('name'=>"michael" ) );

	//[load one record by MongoId]
	$id = "517c850641da6da0ab000004";
	$id = new \MongoId('517c850641da6da0ab000004'); // hah,both ok!
	$user = User::id( $id );

### Load all records

	$users = User::all();

### Lazyload a 1:1 relationship record

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


### Lazyload 1:x relationship records

	$user = User::one();

	$book1 = new Book();
	$book1->name = "book1";
	$book1->save();
	
	$book2 = new Book();
	$book2->name = "book2";
	$book2->save();

	$user->books = array($book1,$book2);
	//also you can
	$user->books = ModelSet::make(array($book1,$book2));
	$user->save();

	//somewhere , load these books
	$user = User::id($id);
	$books = $user->books; 

### let's continue,now is magic ModelSet.... 
	
	$user = User::id($id);
	$books = $user->books; //now books is a instance of Mongodm\ModelSet
	echo $books->count();     // of course it return "2"
	
	$book1 = $books->get(0);  // get a item by index from modelset
	$book1_id = $book1->getId();
	//or 
	$book1 = $books->get($book1_id);  // get a item by mongoid from modelset
	
	echo $books1->name;       
	
	//check item exists in a modelset
	$books->has($book1_id); 
	

	
Special thanks to
-----------------

[mikelbring](https://github.com/mikelbring)
[Paul Hrimiuc](https://github.com/hpaul/)



	
	

