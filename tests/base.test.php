<?php

use Mongodm\ModelSet;
use Mongodm\Test\Model\Book;
use Mongodm\Test\Model\User;

require_once __DIR__.'/../model.php' ;
require_once __DIR__.'/../hydrator.php' ;
require_once __DIR__.'/../mongodb.php' ;
require_once __DIR__.'/../modelset.php' ;
require_once __DIR__.'/model/user.php';
require_once __DIR__.'/model/book.php';


class TestBase extends PHPUnit_Framework_TestCase {

	public function testCreate()
	{
		$user = new User();
		$user->name = "michael";
		$user->save();
		$this->assertEquals("michael", $user->name);
		$this->assertInstanceOf("\MongoId", $user->getId());
		
	}
	
	public function testCreateWithData()
	{
		$user = new User(array("name"=>"John"));
		$user->save();
		$this->assertEquals("John", $user->name);
		$this->assertInstanceOf("\MongoId", $user->getId());
	
	}
	
	public function testSetGet(){
	
		$user = User::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
	
		$book1 = new Book();
		$book1->name = "book1";
		$book1->save();
	
		$book2 = new Book();
		$book2->name = "book2";
		$book2->save();
		$this->assertInstanceOf("\MongoId", $book1->getId());
		$this->assertInstanceOf("\MongoId", $book2->getId());
	
		$user->books = ModelSet::make(array($book1,$book2));
		$user->save();
	
		$user = User::id($id);
		$books = $user->books;
		$book = $books->get($book1->getId());
	
		$this->assertEquals("book1",$book->name);
	
	
	}
	
	public function testAll(){
	
		$user = User::all();
		$this->assertGreaterThan(0, $user->count());

	}
	
	public function testFind(){
	
		$user = User::find(array("name"=>"michael"));
		$this->assertGreaterThan(0, $user->count());
		$user = User::find(array("name"=>"michael_no_exists"));
		$this->assertEquals(0, $user->count());
	
	}
	
	public function testFindOne(){
		
		$user = User::one(array('name'=>'michael'));
		$this->assertEquals("michael", $user->name);
		
	}
	
	public function testUpdate(){
		
		$user = User::one();
		$id = $user->getId();
		$name = $user->name;
		$newName = $name."_new";
		
		$user->array = array(1);
		
		$user->name = $newName;
		$user->save();
		
		$user = User::id($id);
		$this->assertEquals($newName, $user->name);
		$this->assertEquals(array(1), $user->array);
		
	}
	
	public function testDelete(){
	
		$user = User::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
		$user->delete();
		$user = User::id($id);
		$this->assertNull($user);
	
	}
	
	public function testRelation1TO1(){
	
		$user = User::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
		
		$book = new Book();
		$bookName = "Book";
		$book->name = $bookName; 
		$book->save();
		$this->assertInstanceOf("\MongoId", $book->getId());
		
		$user->book_fav = $book;
		$user->save();
		
		$user = User::id($id);
		$book_fav = $user->book_fav;
		
		$this->assertEquals($bookName,$book_fav->name);
	
	}
	
	public function testRelation1TOMany(){
	
		$user = User::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
	
		$book1 = new Book();
		$book1->name = "book1";
		$book1->save();
		
		$book2 = new Book();
		$book2->name = "book2";
		$book2->save();
		$this->assertInstanceOf("\MongoId", $book1->getId());
		$this->assertInstanceOf("\MongoId", $book2->getId());
	
		$user->books = ModelSet::make(array($book1,$book2));
		$user->save();
	
		$user = User::id($id);
		$books = $user->books;
	
		$this->assertEquals(2,$books->count());
		$this->assertEquals("book1",$books->get((string) $book1->getId() )->name);
		$this->assertEquals("book2",$books->get((string) $book2->getId() )->name);
				
		
	}
	
}