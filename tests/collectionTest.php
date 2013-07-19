<?php


use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;

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
	
	public function testReferencesChangedDirectly(){

        $user = new User(array('name'=>'michael'));
        $user->save();

        $book = new Book(array('name'=>'book1'));
        $book->save();

        $books = array($book);

        $user->books = $books;
        $user->save();

        $book_2 = new Book(array('name'=>'book2'));

        $book_2->save();
        $user->books->add($book_2);
        $user->save();

        $book2_id = $book_2->getId();

        $this->assertEquals($user->books->count(),2);

        $id = $user->getId();

        $user = User::id($id);

        $book_3 = new Book(array('name'=>'book3'));
        $book_3->save();
        $user->books->add($book_3);

        $this->assertEquals($user->books->count(),3);

        $user->books->remove($book2_id);

        $user->save();

        $user = User::id($id);

        $this->assertEquals($user->books->count(),2);



    }

    function testEach(){

        $user = new User(array('name'=>'michael'));
        $user->save();

        $user_id = $user->getId();

        $book = new Book(array('name'=>'book1'));
        $book->save();

        $book2 = new Book(array('name'=>'book2'));
        $book2->save();

        $books = array($book,$book2);

        $user->books = $books;
        $user->save();

        $user->books->each(function($item){
             $item->name = '1';
             $item->save();
        });

        $user->save();


        $user = User::id($user_id);


        $user->books->each(function($item){
            $this->assertEquals( $item->name, 1);
        });

    }

}
