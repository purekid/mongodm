<?php

use Purekid\Mongodm\Test\Model\Pupil;

use Purekid\Mongodm\Test\Model\Book;

use Purekid\Mongodm\Test\Model\Student;


class TestInheritance extends PHPUnit_Framework_TestCase {

	
	public function testCreate()
	{ 
		
		$student = new Student();
		$student->name = "michael";
		$student->save();
		$this->assertEquals(16, $student->age);
		
	}
	
	public function testAnother()
	{
	
		$student = new Pupil();
		$student->name = "michael";
		$student->save();
		$this->assertEquals("A", $student->class);
	
	}

	public function testRelation1TO1(){
	
		$user = Student::one();
		$id = $user->getId();
		$this->assertInstanceOf("\MongoId", $user->getId());
	
		$book = new Book();
		$bookName = "Book";
		$book->name = $bookName;
		$book->save();
		$this->assertInstanceOf("\MongoId", $book->getId());
	
		$user->book_fav = $book;
		$user->save();
	
		$user = Student::id($id);
		$book_fav = $user->book_fav;
	
		$this->assertEquals($bookName,$book_fav->name);
	
	}
	
}