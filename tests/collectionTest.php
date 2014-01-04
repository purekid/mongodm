<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Collection;
use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;

class CollectionTest extends PhactoryTestCase
{
    public function testCollectionFunction()
    {
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

    public function testReferencesChangedDirectly()
    {
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

    public function testEach()
    {
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

        $user->books->each(function ($item) {
             $item->name = '1';
             $item->save();
        });

        $user->save();

        $user = User::id($user_id);

        $names = array();
        $user->books->each(function ($item) {
            $names[] = $item->name;
        });

        foreach ($names as $name) {
            $this->assertEquals($name,1);
        }
    }

    public function testSort()
    {
        $books = $this->createBooksCollection(array(
            array('name' => 'b', 'price' => 3), 
            array('name' => 'c', 'price' => 1), 
            array('name' => 'a', 'price' => 2), 
        ));

        $this->assertEquals($books->get(0)->name , 'b');

        $books->sortBy(function ($book) { return $book->name; });

        $this->assertEquals($books->get(0)->name , 'c');

        $books->sortBy(function ($book) { return $book->name; } , true);

        $this->assertEquals($books->get(0)->name , 'a');

        $books->reverse();

        $this->assertEquals($books->get(0)->name , 'c');

        $books->sortBy(function ($book) { return $book->price; } , true);

        $this->assertEquals($books->get(0)->price , 1);
    }

    public function testFilter()
    {
        $books = $this->createBooksCollection(array(
            array('name' => 'b', 'price' => 3), 
            array('name' => 'c', 'price' => 10), 
            array('name' => 'a', 'price' => 18), 
            array('name' => 'd', 'price' => 21), 
        ));

        $books_filter_1 = $books->filter(function ($book) {   if($book->price > 10) return true;  });

        $this->assertEquals( $books_filter_1->count() , 2);

        $this->assertEquals( $books_filter_1->get(1)->name , 'd' );

        $books_filter_2 = $books->filter(function ($book) {   if($book->price % 3 == 0) return true;  });

        $this->assertEquals( $books_filter_2->count() , 3);

        $this->assertEquals( $books_filter_2->get(1)->name , 'a' );
        $this->assertEquals( $books_filter_2->get(2)->name , 'd' );
    }

    public function testMapChangesCollectionItems()
    {
        $books = $this->createBooksCollection(array(
            array('name' => 'b', 'price' => 3), 
            array('name' => 'c', 'price' => 10), 
            array('name' => 'a', 'price' => 18), 
            array('name' => 'd', 'price' => 21), 
        ));

        $books_map_1 = $books->map(function ($book) {   if ($book->price > 10) { $book->price = 99; }  return $book;   });

        $this->assertEquals( $books->get(2)->price , 99);

        $this->assertEquals( $books_map_1->count() , 4);

        $this->assertEquals( $books_map_1->get(2)->price , 99 );
        $this->assertEquals( $books_map_1->get(2)->name , 'a' );

        $books_map_2 = $books->map(function ($book) {   if ($book->price > 10) { $book->price = 99; return $book;}     });

        $this->assertEquals( $books_map_2->count() , 2);
        
        $this->assertEquals( $books_map_2->get(0)->price , 99 );
        $this->assertEquals( $books_map_2->get(0)->name , 'a' );

        $this->assertEquals( $books_map_2->get(1)->price , 99 );
        $this->assertEquals( $books_map_2->get(1)->name , 'd' );
    }

    public function testSlice()
    {
        $books = $this->createBooksCollection(array(array('name' => 'a'), array('name' => 'b'), array('name' => 'c'), array('name' => 'd')));

        $slice1 = $books->slice(0,1);
        $this->assertEquals( $slice1->count(),1);
        $this->assertEquals( $slice1->first()->name,'a');

        $slice = $books->slice(1,3);
        $this->assertEquals( $slice->count(),3);
        $this->assertEquals( $slice->first()->name,'b');
        $this->assertEquals( $slice->get(2)->name,'d');

        $slice = $books->slice(2,4);
        $this->assertEquals( $slice->count(),2);
        $this->assertEquals( $slice->first()->name,'c');
        $this->assertEquals( $slice->last()->name,'d');
    }

    public function testTake()
    {
        $books = $this->createBooksCollection(array(array('name' => 'a'), array('name' => 'b'), array('name' => 'c'), array('name' => 'd')));

        $take = $books->take(3);

        $this->assertEquals( $take->count(),3);
        $this->assertEquals( $take->first()->name,'a');
        $this->assertEquals( $take->last()->name,'c');
    }

    public function testGetReturnsNullWhenIndexTooHigh()
    {
        $collection = Collection::make(array());
        $value = $collection->get(1);
        $this->assertNull($value);
    }

    public function testGetReturnsNullWhenStringIndexNotFound()
    {
        $collection = $this->createBooksCollection(array(array('name' => 'a')));
        $value = $collection->get('b');
        $this->assertNull($value);
    }
   
    public function testIsEmptyReturnsTrueWhenCollectionHasNoContents()
    {
        $collection = Collection::make(array());
        $this->assertTrue($collection->isEmpty(), "Expected empty Collection to return true for isEmpty()");
    }

    public function testGetIteratorReturnsAnIterator()
    {
        $books = $this->createBooksCollection(array(array('name' => 'a'), array('name' => 'b'), array('name' => 'c'), array('name' => 'd')));
        $it = $books->getIterator();
        $this->assertInstanceOf('ArrayIterator', $it);
        foreach($it as $book) {
            $this->assertInstanceOf('\Purekid\Mongodm\Test\Model\Book', $book);
        }
    }

    protected function createBooksCollection(array $data)
    {
        $books = array();
        foreach($data as $item){
            $book = new Book($item);
            $book->save();
            $books []= $book;
        }
        return Collection::make($books);
    }
}
