<?php


use Purekid\Mongodm\Test\Model\Book;


class TestCollection extends PHPUnit_Framework_TestCase {

	public function testCollectionFunction(){
	
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
		
		$books_count = $books->count();
		$this->assertTrue($books->has($book3));
		$books->remove($book3);
		$this->assertTrue(! $books->has($book3) );
		
		$this->assertEquals($books_count - 1, $books->count());
	
	}
	
	
}