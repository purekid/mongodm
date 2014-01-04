<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Collection;
use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;

class CollectionTest extends PhactoryTestCase
{
    protected $ordered_books;

    public function testCollectionFunction()
    {
        $book1 = $this->createBook(array('name' => 'book1', 'price' => 5));
        $book2 = $this->createBook(array('name' => 'book2', 'price' => 10));
        $book3 = $this->createBook(array('name' => 'book3', 'price' => 15));

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

        $book = $this->createBook(array('name' => 'book1', 'price' => 5));

        $books = array($book);

        $user->books = $books;
        $user->save();

        $book_2 = $this->createBook(array('name' => 'book2', 'price' => 10));

        $user->books->add($book_2);
        $user->save();

        $book2_id = $book_2->getId();

        $this->assertEquals($user->books->count(),2);

        $id = $user->getId();

        $user = User::id($id);

        $book_3 = $this->createBook(array('name' => 'book3', 'price' => 15));
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

        $book = $this->createBook(array('name' => 'book1', 'price' => 5));
        $book2 = $this->createBook(array('name' => 'book2', 'price' => 10));

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
            array('name' => 'c', 'price' => 10),
            array('name' => 'a', 'price' => 18),
            array('name' => 'd', 'price' => 21),
        ));

        $this->assertEquals($books->get(0)->name, 'b');

        $books->sortBy(function ($book) { return $book->name; });

        $this->assertEquals($books->get(0)->name, 'd');

        $books->sortBy(function ($book) { return $book->name; } , true);

        $this->assertEquals($books->get(0)->name, 'a');

        $books->reverse();

        $this->assertEquals($books->get(0)->name, 'd');

        $books->sortBy(function ($book) { return $book->price; } , true);

        $this->assertEquals($books->get(0)->price, 3);
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
        $this->givenAnOrderCollectionOfBooks();

        $slice1 = $this->ordered_books->slice(0,1);
        $this->assertEquals( $slice1->count(),1);
        $this->assertEquals( $slice1->first()->name,'a');

        $slice = $this->ordered_books->slice(1,3);
        $this->assertEquals( $slice->count(),3);
        $this->assertEquals( $slice->first()->name,'b');
        $this->assertEquals( $slice->get(2)->name,'d');

        $slice = $this->ordered_books->slice(2,4);
        $this->assertEquals( $slice->count(),2);
        $this->assertEquals( $slice->first()->name,'c');
        $this->assertEquals( $slice->last()->name,'d');
    }

    public function testTake()
    {
        $this->givenAnOrderCollectionOfBooks();

        $take = $this->ordered_books->take(3);

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
        $this->givenAnOrderCollectionOfBooks();

        $value = $this->ordered_books->get('e');

        $this->assertNull($value);
    }
   
    public function testIsEmptyReturnsTrueWhenCollectionHasNoContents()
    {
        $collection = Collection::make(array());

        $this->assertTrue($collection->isEmpty(), "Expected empty Collection to return true for isEmpty()");
    }

    public function testGetIteratorReturnsAnIterator()
    {
        $this->givenAnOrderCollectionOfBooks();

        $it = $this->ordered_books->getIterator();

        $this->assertInstanceOf('ArrayIterator', $it);
        foreach($it as $book) {
            $this->assertInstanceOf('\Purekid\Mongodm\Test\Model\Book', $book);
        }
    }

    public function testToArrayConvertsItemToArray()
    {
        $this->givenAnOrderCollectionOfBooks();

        $result = $this->ordered_books->toArray(true, true);

        foreach ($result as $item) {
            $this->assertInternalType('array', $item);
            $this->assertInstanceOf('MongoId', $item['_id']);
        }
    }

    public function testToArrayReturnsArrayWithNonNumericIndexes()
    {
        $this->givenAnOrderCollectionOfBooks();

        $result = $this->ordered_books->toArray(false, false);

        foreach ($result as $key => $book) {
            $this->assertInternalType('string', $key);
            $this->assertInstanceOf('\Purekid\Mongodm\Test\Model\Book', $book);
            $this->assertSame($key, (string) $book->getId());
        }
    }

    public function testToArrayReturnsArrayOfArraysWithNonNumericIndexes()
    {
        $this->givenAnOrderCollectionOfBooks();

        $result = $this->ordered_books->toArray(false, true);

        foreach ($result as $key => $book) {
            $this->assertInternalType('string', $key);
            $this->assertInternalType('array', $book);
            $this->assertSame($key, (string) $book['_id']);
        }
    }

    public function testHasAcceptsMongoId()
    {
        $this->givenAnOrderCollectionOfBooks();

        $id = $this->ordered_books->get(0)->getId();

        $result = $this->ordered_books->has($id);
        $this->assertTrue($result, "Expected has() to return true for existing Book id");
    }

    public function testHasReturnsFalseForMissingMongoId()
    {
        $this->givenAnOrderCollectionOfBooks();
        $id = new \MongoId;

        $result = $this->ordered_books->has($id);
        $this->assertFalse($result, "Expected has() to return false for missing id");
    }

    public function testOffsetGetReturnsModelForIntegerIndex()
    {
        $this->givenAnOrderCollectionOfBooks();
        $id = 1;

        $result = $this->ordered_books[$id];

        $this->assertInstanceOf('\Purekid\Mongodm\Test\Model\Book', $result);
    }

    public function testOffsetGetReturnsModelForStringIndex()
    {
        $this->givenAnOrderCollectionOfBooks();
        $id = $this->ordered_books->get(1)->getId();

        $result = $this->ordered_books[$id];

        $this->assertInstanceOf('\Purekid\Mongodm\Test\Model\Book', $result);
    }

    public function testOffsetUnsetRemovesModelForIntegerIndex()
    {
        $this->givenAnOrderCollectionOfBooks();
        $id = 1;

        unset($this->ordered_books[$id]);

        $this->assertSame(3, $this->ordered_books->count());
    }

    public function testOffsetUnsetRemovesModelForModelIndex()
    {
        $this->givenAnOrderCollectionOfBooks();
        $id = $this->ordered_books->get(0);

        unset($this->ordered_books[$id]);

        $this->assertSame(3, $this->ordered_books->count());
    }

    /**
     * @expectedException Exception
     */
    public function testCannotSetModelWithOffsetSet()
    {
        $this->givenAnOrderCollectionOfBooks();
        $this->ordered_books[1] = $this->createBook(array('name' => 'e'));
    }

    public function testIssetReturnsTrueForItemInCollectionWithIntegerIndex()
    {
        $this->givenAnOrderCollectionOfBooks();

        $result = isset($this->ordered_books[3]);

        $this->assertTrue($result, "Expected isset() to return true");
    }

    public function testIssetReturnsFalseForItemNotInCollectionWithIntegerIndex()
    {
        $this->givenAnOrderCollectionOfBooks();

        $result = isset($this->ordered_books[17]);

        $this->assertFalse($result, "Expected isset() to return false");
    }

    protected function givenAnOrderCollectionOfBooks()
    {
        $this->ordered_books = $this->createBooksCollection(
            array(
                array('name' => 'a', 'price' => 5),
                array('name' => 'b', 'price' => 10),
                array('name' => 'c', 'price' => 15),
                array('name' => 'd', 'price' => 20),
            )
        );
    }

    protected function createBooksCollection(array $data)
    {
        $books = array();
        foreach($data as $item){
            $books []= $this->createBook($item);
        }
        return Collection::make($books);
    }

    protected function createBook(array $item)
    {
        $book = new Book($item);
        $book->save();
        return $book;
    }
}
