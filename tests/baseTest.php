<?php

use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;
use Purekid\Mongodm\ModelSet;

class TestBase extends PHPUnit_Framework_TestCase {

	
	public function testCreate()
	{ 
		
		$user = new User();
		$user->name = "michael";
		$user->save();
		$this->assertEquals("michael", $user->name);
		$this->assertInstanceOf("\MongoId", $user->getId());
		
	}
	
	public function testDefaultAttr(){
		
		$user = new User();
		$user->name = "michael";
		$this->assertEquals((int)16, $user->age);
		$this->assertEquals((float) 20.0, $user->money);
		$user->save();
		$id = $user->getId();
		
		$user = User::id($id);
		
		$this->assertEquals((int) 16, $user->age);
		$this->assertEquals((float) 20.0, $user->money);
		
	}
	
	public function testAttrType(){
		
		$user = new User();
		$user->name = "michael";
		$family = (object) array('mum'=>"Lisa",'Dad'=>'Bob');
		$user->family = $family;
		$user->save();
		$this->assertObjectHasAttribute("mum", $user->family);
		$this->assertEquals("Lisa", $user->family->mum);
		
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
		
		$user->name = "abcd";
		$user->books = array($book1,$book2);
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
		
		$user->test_data = array(1);
		
		$user->name = $newName;
		$user->save();
		
		$user = User::id($id);
		$this->assertEquals($newName, $user->name);
		$this->assertEquals(array(1), $user->test_data);
		
	}
	
	public function testDelete(){
	
		$user = User::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
		$user->delete();
		$user = User::id($id);
		$this->assertNull($user);
	
	}
	
	public function testSetFunction(){
	
		$book1 = new Book();
		$book1->name = "book1";
		$book1->price = 5;
		$book1->save();
		
		$book2 = new Book();
		$book2->name = "book2";
		$book2->price = 10;
		$book2->save();
		
		$book3 = new Book();
		$book3->name = "book3";
		$book3->price = 15;
		$book3->save();
		
		
		$books = Book::find(array('price'=>5));
		$books_count = $books->count();
		$books2 = Book::find(array('price'=>array('$gt'=>5)));
		$books2_count = $books2->count();
		$books->add($books2);
		$this->assertEquals($books->count(), $books_count + $books2_count);		
	
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