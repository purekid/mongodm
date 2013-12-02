Mongodm    
======= 
[![Build Status](https://secure.travis-ci.org/purekid/mongodm.png?branch=master)](http://travis-ci.org/purekid/mongodm)


- [Introduction](#introduction)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup Database](#setup-database)
- [Basic Usage - CRUD](#model-crud)
- [Relationship - Reference](#relationship---reference)
- [Relationship - Embed](#relationship---embed)
- [Collection](#collection)
- [Inheritance](#inheritance)
- [Other methods](#other-static-methods-in-model)
- [Model Hooks](#model-hooks)
- [Special Thanks](#special-thanks-to)


Introduction
------------
Mongodm is a MongoDB ORM that includes support for references,embed and even multilevel inheritance.

Features
--------

- ORM
- Simple and flexible
- Support for embed 
- Support for references (lazy loaded)
- Support for multilevel inheritance
- Support for local collection operations

Requirements
------------
- PHP 5.3 or greater
- Mongodb 1.3 or greater
- PHP Mongo extension 

Installation
----------

### 1. Setup in composer.json: 
```yml
	{
		"require": {
		    "purekid/mongodm": "dev-master"
		}
	}
```

### 2. Install by composer:

	$ php composer.phar update
		

Setup Database
----------

### Setup database in   config/database.php
If you want select config section with environment variable APPLICATION_ENV , you should set $config='default' or don't declare $config in your own model class.
```php
	return array(
        'default' => array(
    		'connection' => array(
    			'hostnames' => 'localhost',
    			'database'  => 'default',
    // 			'username'  => '',
    // 			'password'  => '',
    		)
    	),
    	'production' => array(
    		'connection' => array(
    			'hostnames' => 'localhost',
    			'database'  => 'production'
    		)
    	)
    );
```
### Create a model and enjoy it

```php       
    class User extends \Purekid\Mongodm\Model 
    {
    
        static $collection = "user";
        
        /** use specific config section **/
        public static $config = 'testing';
        
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
            'family'=>array('type'=>'object'),
            'pet_fav' => array('model'=>'Purekid\Mongodm\Test\Model\Pet','type'=>'embed'),
            'pets' => array('model'=>'Purekid\Mongodm\Test\Model\Pet','type'=>'embeds'),
                
        );

        public function setFirstName($name) {
        	$name = ucfirst(strtolower($name));
        	$this->__setter('firstName', $name);
        }

        public function getLastName($name) {
        	$name = $this->__getter('name');
        	return strtoupper($name);
        }
    
    }
```    

### Types supported for model attributes

```php
	$types = [
	    'mixed',  // mixed type 
	    'string',     
	    'reference',  // 1 ： 1 reference
	    'references', // 1 ： many references
	    'embed', 
	    'embeds', 
	    'integer',  
	    'int',  // alias of 'integer'
	    'double',     // float 
	    'timestamp',  // store as MongoTimestamp in Mongodb
	    'boolean',    // true or false
	    'array',    
	    'object'
	]
```
Model CRUD
---------- 

### Create 
```php  
	$user = new User();
	$user->name = "Michael";
	$user->age = 18;
	$user->save();
``` 
Create with initial value
```php  
	$user = new User( array('name'=>"John") );
	$user->age = 20;
	$user->save();
```

Create using set method
```php
	$user->setLastName('Jones'); // Alias of $user->lastName = 'Jones';
	$user->setFirstName('John'); // Implements setFirstName() method
```

#### Set and get values
You can set/get values via variable `$user->name = "John"` or by method `$user->getName()`.

Set using variable or method
```php
 	// no "set" method exists
	$user->lastName = 'Jones';
	$user->setLastName('Jones');

	// "set" method exists implements setFirstName()
	$user->firstName = 'jOhn'; // "John"
	$user->setFirstName('jOhn'); // "John"
```

Get using variable or method
```php
 	// "get" method exists implements getLastName()
	print $user->lastName; // "JONES"
	print $user->getLastName(); // "JONES"

	// no "get" method
	print $user->firstName; // "John"
	print $user->setFirstName('John'); // "John"
```

### Update
```php  
	$user->age = 19;
```
Update attributes by array
```php  
	$user->update( array('age'=>18,'hobbies'=>array('music','game') ) ); 
	$user->save();
```
Unset attributes
```php  	
	$user->unset('age');
	$user->unset( array('age','hobbies') )
```
### Retrieve single record
```php  
	$user = User::one( array('name'=>"michael" ) );
```	
retrieve one record by MongoId
```php  
	$id = "517c850641da6da0ab000004";
	$id = new \MongoId('517c850641da6da0ab000004'); //another way
	$user = User::id( $id );
```	
### Retrieve records

Retrieve records that name is 'Michael' and acount  of owned  books equals 2
```php  
	$params = array( 'name'=>'Michael','books'=>array('$size'=>2) );
	$users = User::find($params);     // $users is instance of Collection
	echo $users->count();
```      
### Retrieve all records
```php  
	$users = User::all();
```	
### Count records
```php  
	$count = User::count(array('age'=>16));
```
### Delete record
```php  
	$user = User::one();
	$user->delete();	
```	
Relationship - Reference
---------- 
### Lazyload a 1:1 relationship record
```php  
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
```
### Lazyload 1:many relationship records
```php  
	$user = User::one();
	$id = $user->getId();

	$book1 = new Book();
	$book1->name = "book1";
	$book1->save();
	
	$book2 = new Book();
	$book2->name = "book2";
	$book2->save();

	$user->books = array($book1,$book2);
	//also you can
	$user->books = Collection::make(array($book1,$book2));
	$user->save();

	//somewhere , load these books
	$user = User::id($id);
	$books = $user->books;      // $books is a instance of Collection
```
Relationship - Embed
---------- 

### Single Embed 
```php  
	$pet = new Pet();
	$pet->name = "putty";

	$user->pet_fav = $pet;
	$user->save();

	// now you can do this
	$user = User::one( array('name'=>"michael" ) );
	echo $user->pet_fav->name;
```
### Embeds
```php  
	$user = User::one();
	$id = $user->getId();
	
	$pet_dog = new Pet();
	$pet_dog->name = "puppy";
	$pet_dog->save();
	
	$pet_cat = new Pet();
	$pet_cat->name = "kitty";
	$pet_cat->save();

	$user->pets = array($pet_cat,$pet_dog);
	//also you can
	$user->pets = Collection::make(array($pet_cat,$pet_dog));
	$user->save();

	$user = User::id($id);
	$pets = $user->pets;     
```
###  Collection 

$users is instance of Collection
```php  
	$users = User::find(  array( 'name'=>'Michael','books'=>array('$size'=>2) ) );    
	$users_other = User::find(  array( 'name'=>'John','books'=>array('$size'=>2) ) );   
```	
Count 
```php  
	$users->count();  
	$users->isEmpty();
```	
Iteration	
```php  
	foreach($users as $user) { }  
	
	// OR use Closure 
	
	$users->each(function($user){
	
	})
```	
Sort
```php  
	//sort by age desc
	$users->sortBy(function($user){
	    return $user->age;
	});
	
	//sort by name asc
	$users->sortBy(function($user){
	    return $user->name;
	} , true);
	
	//reverse collection items
	$users->reverse();
```
Slice and Take
```php  	
	$users->slice(0,1);
	$users->take(2);
```	
Map
```php  	
	$func = function($user){
		  		if( $user->age >= 18 ){
		    		$user->is_adult = true;
		    		return $user;
	        	}
			};
	
	$adults = $users->map($func);   
	
	// Notice:  1. $adults is a new collection   2. In original $users , data has changed at the same time. 
```	
Filter 
```php  
	$func = function($user){
	        	if( $user->age >= 18 ){
	    			return true;
	    		}
			}

	$adults = $users->filter($func); // $adults is a new collection
```
Determine a record exists in the collection by object instance	
```php  	
	$john = User::one(array("name"=>"John"));
	
	$users->has($john) 
```
Determine a record exists in the collection by numeric index	
```php  
	$users->has(0) 
```	
Determine a record exists in the collection by MongoID	
```php  
	$users->has('518c6a242d12d3db0c000007') 
```
Get a record by numeric index
```php  
	$users->get(0) 
```
Get a record by MongoID 
```php  
	$users->get('518c6a242d12d3db0c000007') 
```
Remove a record by numeric index
```php  
	$users->remove(0)  
```
Remove a record  by MongoID
```php  
	$users->remove('518c6a242d12d3db0c000007') 
```	
Add a single record to collection
```php  
	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$users->add($bob);
```	
Add records to collection
```php  	
	$bob = new User( array("name"=>"Bob"));
	$bob->save();
	$lisa = new User( array("name"=>"Lisa"));
	$lisa->save();
	
	$users->add( array($bob,$lisa) ); 
```	
Merge two collection 
```php  	
	$users->add($users_other);  // the collection $users_other appends to end of $users 
```
Export data to a array
```php  
	$users->toArray();
```	
Inheritance
----------
	
### Define multilevel inheritable models:
```php  
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
	
		protected static $attrs = array(
			'grade' => array('type'=>'string'),
			'classmates' => array('type'=>'references','model'=>'Demo\Student'),
		)
		
	}
```	
### Use:
```php  
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
```	
### Retrieve and check value:
```php  
	$bob = Student::one( array("name"=>"Bob") );
	
	echo $bob->dad->name;    // David
	
	$classmates = $bob->classmates;
	
	echo $classmates->count(); // 2
    
	var_dump($classmates->get(0)); // john	
```	

### Retrieve subclass

Retrieve all Human records , queries without '_type' because of it's a toplevel class.
```php  	
    $humans = Human::all();
``` 
Retrieve all Student records , queries with  { "_type":"Student" } because of it's a subclass.
```php  
    $students = Student::all();
```

Other static methods in Model
----------
```php
	User::drop() // Drop collection 
	User::ensureIndex()  // Add index for collection
```
Model Hooks
----------
The following hooks are available:

##### __init()

Executed after the constructor has finished

##### __preInsert()

Executed before saving a new record

##### __postInsert()

Executed after saving a new record

##### __preUpdate()

Executed before saving an existing record

##### __postUpdate()

Executed after saving an existing record

##### __preSave()

Executed before saving a record

##### __postSave()

Executed after saving a record

##### __preDelete()

Executed before deleting a record

##### __postDelete()

Executed after deleting a record

Special thanks to
-----------------

[mikelbring](https://github.com/mikelbring)
[Paul Hrimiuc](https://github.com/hpaul/)


	
	

