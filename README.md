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
- Support for multilevel inheritance

Installation
--------

1. Setup in composer.json: 
 
	{
		"require": {
		"purekid/mongodm": "dev-master",
		}
	}


2. Install by composer:

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
		),
		'test' => array(
			'connection' => array(
				'hostnames' => 'localhost',
				'database'  => 'test',
	// 			'username'  => '',
	// 			'password'  => '',
			)
		)
	);

### Define a model and enjoy it

    use Purekid\Mongodm\Model;
        
    class User extends Model 
    {
    
        static $collection = "user";
        public static $config = 'test';
        
        /** specific definition for attributes, not necessary! **/
        protected static $attrs = array(
                
             // 1 to 1 reference
            'book_fav' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'reference'),
             // 1 to many references
            'books' => array('model'=>'Purekid\Mongodm\Test\Model\Book','type'=>'references'),
            // you can define default value for attribute
            'age' => array('default'=>16,'type'=>'integer'),
            'money' => array('default'=>20.0,'type'=>'double'),
            'hobbies' => array('default'=>array('love'),'type'=>'array'),
            'born_time' => array('type'=>'timestamp'),
            'family'=>array('type'=>'object')
                
        );
    
    }
    
Types Supported for model attr
----------   

	$types = [
	  	'string',     // default type 
	    'reference',  // a reference to another model
	    'references', // references to another model
	    'integer',  
	    'double',     // float 
	    'timestamp',  // store as MongoTimestamp in Mongodb
	    'boolean',    // true or false
	    'array',    
	    'object'
	]

CRUD
---------- 

### Create 
	$user = new User();
	$user->name = "Michael";
	$user->save();
    
    //Create with initial value
	$user = new User( array('name'=>"John") );
	$user->save();

### Update
	$user->name = 20;
	//Update attrs by array
	$user->update( array('age'=>18,'hobbies'=>array('music','game') ) ); 
	$user->save();

### Retrieve single record
	$user = User::one( array('name'=>"michael" ) );
	//[load one record by MongoId]
	$id = "517c850641da6da0ab000004";

	$id = new \MongoId('517c850641da6da0ab000004'); //another way
	$user = User::id( $id );
	
### Retrieve records
       // retrieve records that name is 'Michael' and acount  of owned  books equals 2
       $params = array( 'name'=>'Michael','books'=>array('$size'=>2) );
       $users = User::find($params);     // $users is instance of ModelSet
       echo $users->count();
       
### Retrieve all records
	$users = User::all();

### Delete record
	$user = User::one();
	$user->delete();	
	


Relationship
---------- 
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

### Lazyload 1:many relationship records

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

###  ModelSet 

$users is instance of ModelSet

	$users = User::find(  array( 'name'=>'Michael','books'=>array('$size'=>2) ) );    
	$users_other = User::find(  array( 'name'=>'John','books'=>array('$size'=>2) ) );   
	
Count 

	$users->count();  
	
Iteration	

	foreach($users as $user) { }  
	
Determine a record exists in the set by numeric index	

	$users->has(0) 
	
Determine a record exists in the set by MongoID	

	$users->has('518c6a242d12d3db0c000007') 

Get a record by numeric index

	$users->get(0) 

Get a record by MongoID 

	$users->get('518c6a242d12d3db0c000007') 

Remove a record by numeric index

	$users->remove(0)  

Remove a record  by MongoID

	$users->remove('518c6a242d12d3db0c000007') 
	
Add a single record to set

	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$users->add($bob);
	
Add records to set
	
	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$lisa = new User( array("name"=>"Lisa"));
	$lisa->save();
	
	$users->add( array($bob,$lisa) ); 
	
Merge two set 
	
	$users->add($users_other);  // the set $users_other appends to end of $users 
	
Export data to a array

	$users->toArray();
	
Inheritance
----------
### Wonderful multilevel inheritance.
	
Define models:

	use Purekid\Mongodm\Model;
	namespace Demo;
	
	class Human extends Model{
	
		static $collection = "human";
		
		protected static $attrs = array(
			'name' => array('default'=>'anonym','type'=>'string'),
			'age' => array('type'=>'integer'),
			'gender' => array('type'=>'string'),
			'dad' =>  array('type'=>'reference','model'=>'Demo\Human'),
			'mum' =>  array('type'=>'reference','model'=>'Demo\Human'),
			'friends' => array('type'=>'references','model'=>'Demo\Human'),
		)
	
	}

	class Student extends Human{
	
		static $collection = "human";
		
		protected static $attrs = array(
			'grade' => array('type'=>'string'),
			'classmates' => array('type'=>'references','model'=>'Demo\Student'),
		)
		
	}
	
To use:

	$bob = new Student( array('name'=>'Bob','age'=> 17 ,'gender'=>'male' ) );
	$bob->save();
	
	$john = new Student( array('name'=>'John','age'=> 16 ,'gender'=>'male' ) );
	$john->save();
	
	$lily = new Student( array('name'=>'Lily','age'=> 16 ,'gender'=>'female' ) );
	$lily->save();
	
	$lisa = new Human( array('name'=>'Lisa','age'=>41 ,'gender'=>'female' ) );
	$lisa->save();
	
	$david = new Human( array('name'=>'David','age'=>42 ,'gender'=>'male') );
	$david->save();
	
	$bob->dad = $david;
	$bob->mum = $lisa;
	$bob->classmates = array( $john, $lily );
	$bob->save();
	
	
Now you can:

	$bob = Student::one( array("name"=>"Bob") );
	echo $bob->dad->name;    // David
	
	$classmates = $bob->classmates;
	echo $classmates->count(); // 2
	var_dump($classmates->get(0)); // john	


Hooks
---------- 
	
	__beforeDelete(){
		// invoke before delete()
	}
	
	__beforeSave(){
		// invoke before save()
	}


Special thanks to
-----------------

[mikelbring](https://github.com/mikelbring)
[Paul Hrimiuc](https://github.com/hpaul/)


	
	

