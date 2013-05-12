Mongodm  
======= 
[![Build Status](https://secure.travis-ci.org/purekid/mongodm.png?branch=master)](http://travis-ci.org/purekid/mongodm)

a PHP MongoDb ORM ,  simple and flexible

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

Installation
--------
config in composer.json: 
 
{
    "require": {
        "purekid/mongodm": "dev-master",
    }
}


php composer.phar install


How to Use
----------

### Setup database in   config/database.php

		return array(
			'default' => array(
				'connection' => array(
					'hostnames' => 'localhost',
					'database'  => 'dbname',	
		// 			'username'  => '',
		// 			'password'  => '',	
				)
			)
		);

### Define a model
    use Purekid\Mongodm\Model;
        
    class User extends Model 
    {
    
        static $collection = "user";
        public static $config = 'test';
        
        /* specific definition for attributes, not necessary. */
        protected static $attrs = array(
                
             // 1 to 1 reference
            'book_fav' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'reference'),
             // 1 to many references
            'books' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'references'),
            // you can define default value for attribute
            'age' => array('default'=>16,'type'=>'integer'),
            'money' => array('default'=>20.0,'type'=>'double'),
            'hobbies' => array('default'=>array('love'),'type'=>'array'),
            'born_time => array('type'=>'timestamp'),
            'family'=>array('type'=>'object')
                
        );
    
    }
    
### Type supported for attribute

    'reference','references','integer','double','timestamp','boolean','array','object'
    
### Create model instance
	$user = new User();
	$user->name = "Michael";
	$user->save();
### Create instance with data
	$user = new User( array('name'=>"John") );
	$user->save();
	
### Update data by array
	$user->update( array('age'=>18,'hobbies'=>array('music','game') ) ); 
	$user->save();
	
### Load one record
	$user = User::one( array('name'=>"michael" ) );
	//[load one record by MongoId]
	$id = "517c850641da6da0ab000004";

	$id = new \MongoId('517c850641da6da0ab000004'); //another way
	$user = User::id( $id );
### Load some records
       // retrieve records that name is 'Michael' and acount  of owned  books equals 2
       $params = array( 'name'=>'Michael','books'=>array('$size'=>2) );
       $users = User::find($params);     // $users is instance of ModelSet
       echo $users->count();
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
	$books = $user->books;      // $books is a instance of ModelSet

###  ModelSet , a set of models

	// $users is instance of ModelSet
	$users = User::find(  array( 'name'=>'Michael','books'=>array('$size'=>2) ) );    
	$users_other = User::find(  array( 'name'=>'John','books'=>array('$size'=>2) ) );    
	
	$users->count(); // Count
	
	foreach($users as $user) { } // Iteration
	
	$users->has(0) // determine a record exists in the set by numeric index
	
	$users->has('518c6a242d12d3db0c000007') // determine a record exists in the set by MongoID
	
	$users->get(0) // get a record by numeric index
	
	$users->get('518c6a242d12d3db0c000007') // get a record by MongoID 
	
	$users->remove(0) // remove a record by numeric index
	
	$users->remove('518c6a242d12d3db0c000007') // remove a record  by MongoID 
	
	/* add a record */
	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$users->add($bob);
	
	/* add records */
	
	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$lisa = new User( array("name"=>"Lisa"));
	$lisa->save();
	
	$users->add(array($bob,$lisa)); // add some records
	
	/*  merge two set */
	
	$users->add($users_other);  // the set $users_other appends to end of $users 
	
	/*  export data to a array */
	$users->toArray();
	
	
Special thanks to
-----------------

[mikelbring](https://github.com/mikelbring)
[Paul Hrimiuc](https://github.com/hpaul/)


	
	

