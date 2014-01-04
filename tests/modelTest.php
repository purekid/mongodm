<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Test\Model\Pet;
use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;
use Purekid\Mongodm\Collection;

class ModelTest extends PhactoryTestCase
{
    public function testCreate()
    {

        $user = new User();
        $user->name = "michael";
        $user->save();
        $this->assertEquals("michael", $user->name);
        $this->assertInstanceOf("\MongoId", $user->getId());

    }

    public function testGetConection()
    {
        $user = new User();

        $connection = $user->_getConnection();

        $this->assertInstanceOf('\\Purekid\\Mongodm\\MongoDB', $connection);
    }

    public function testGetCollection()
    {
        $user = new User();

        $collection = $user->_getCollection();

        $this->assertInstanceOf('\\MongoCollection', $collection);
    }

    public function testSaveModelWithMongoId()
    {

        $id = new \MongoId();
        $user = new User(array('_id'=>$id,'name'=>'michael','age'=>21));
        $user->save();
        $this->assertEquals($id, $user->getId());

        $user = User::id($id);
        $this->assertEquals(21, $user->age);

        try{
            $user = new User(array('_id'=>$id,'name'=>'michael','age'=>21));
            $user->save();
        }catch(\MongoCursorException $e){
            $this->assertEquals(11000,$e->getCode());
        }

    }

    public function testDefaultAttr()
    {

        $user = new User();
        $user->name = "michael";
        $this->assertEquals((int) 16, $user->age);
        $this->assertEquals((float) 20.0, $user->money);
        $user->save();
        $id = $user->getId();

        $user = User::id($id);

        $this->assertEquals((int) 16, $user->age);
        $this->assertEquals((float) 20.0, $user->money);

    }

    public function testAttrType()
    {

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

        $book = new Book(array("name"=>"Love"));
        $book->save();

        $user = new User(array("age"=>40,"name"=>"John","book_fav"=>$book));
        $user->save();

        $id = $user->getId();

        $user = User::id($id);
        $this->assertEquals("John", $user->name);
        $this->assertEquals("Love", $user->book_fav->name);
        $this->assertInstanceOf("\MongoId", $user->getId());

    }

    public function testSetGet()
    {
    $user = new User(array("age"=>40,"name"=>"John"));
    $user->save();
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

        $user->books = array($book1,$book2);
        $user->save();
        $user = User::id($id);
        $books = $user->books;
        $book = $books->get($book1->getId());

        $this->assertEquals("book1",$book->name);

    }

  public function testMagicCall()
  {
    $user = new User(array('age' => 40, 'name' => 'testMagicCall'));
    $user->save();
    $user = User::one(array('name' => 'testMagicCall'));

    $user->setTestNameMagic('testMagicCall');
    $testName = $user->getTestNameMagic();

    $this->assertEquals('testMagicCall', $testName, 'Magic getting and setting failed');
  }

  public function testGetSetMethod()
  {
    $user = new User(array('age' => 40, 'name' => 'testGetSetMethod'));
    $user->save();
    $user = User::one(array('name' => 'testGetSetMethod'));

    $user->setTestSetMethod('testSetMethod');
    $testSetMethod = $user->testSetMethod;

    $user->testGetMethod = 'testGetMethod';
    $testGetMethod = $user->getTestGetMethod();

    $this->assertEquals('testsetmethod', $testSetMethod, 'Custom set method failed');
    $this->assertEquals('TESTGETMETHOD', $testGetMethod, 'Custom get method failed');
  }

    public function testAll()
    {

        $user = User::all();
        $this->assertGreaterThan(0, $user->count());

    }

    public function testFind()
    {

        $user = User::find(array("name"=>"michael"));
        $this->assertGreaterThan(0, $user->count());
        $user = User::find(array("name"=>"michael_no_exists"));
        $this->assertEquals(0, $user->count());

    }

    public function testFindWithLimit()
    {
        $user = User::find($params = array('name'=>'michael'), $sort = array(), $fields = array(), $limit = 5);
        $this->assertGreaterThan(0, $user->count());
    }

    public function testFindWithSkip()
    {
        $user = User::find($params = array('name'=>'michael'), $sort = array(), $fields = array(), $limit = null, $skip = 5);
        $this->assertSame(0, $user->count());
    }

    public function testFindWithSort()
    {
        $user = User::find($params = array('name'=>'michael'), $sort = array('name'));
        $this->assertGreaterThan(0, $user->count());
    }

    public function testFindOne()
    {
        $user = User::one(array('name'=>'michael'));
        $this->assertEquals("michael", $user->name);
    }

    public function testUpdate()
    {
    $user = new User(array("age"=>40,"name"=>"John"));
    $user->save();
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

    public function testDelete()
    {
    $user = new User(array("age"=>40,"name"=>"John"));
    $user->save();
        $user = User::one();
        $id = $user->getId();
        $this->assertInstanceOf("\MongoId", $user->getId());
        $user->delete();
        $user = User::id($id);
        $this->assertNull($user);

    }

    public function testRelation1TO1()
    {

    $user = new User(array("age"=>40,"name"=>"John"));
    $user->save();
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

    public function testRelation1TOMany()
    {

    $user = new User(array("age"=>40,"name"=>"John"));
    $user->save();
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

        $user->books = Collection::make(array($book1,$book2));
        $user->save();

        $user = User::id($id);
        $books = $user->books;

        $this->assertEquals(2,$books->count());
        $this->assertEquals("book1",$books->get((string) $book1->getId() )->name);
        $this->assertEquals("book2",$books->get((string) $book2->getId() )->name);

    }

    public function testUnset()
    {
        $user = new User;
        $user->name = 'michael';
        $user->save();
        $id = $user->getId();

        $user = User::id($id);

        $this->assertEquals($user->name , 'michael');

        $user->unset('name');
        $user->save();

        $user = User::id($id);

        $this->assertNull($user->name);

        $user->age = 11;
        $user->hobbies = array('watching tv','music');
        $user->save();

        $user = User::id($id);

        $this->assertEquals($user->age , 11);

        $user->unset(array('age','hobbies'));

        $user->save();

        $user = User::id($id);
        $this->assertNull($user->age);

        $this->assertNull($user->hobbies);

    }

    public function testEmbed()
    {
        $user = new User;
        $user->name = 'michael';
        $user->save();

        $id = $user->getId();

        $pet = new Pet();

        $user->pet = $pet;
        $user->save();

        $user->pet->age = 17;
        $user->save();

        $this->assertEquals(17, $user->pet->age );

        $user = User::id($id);

        $this->assertEquals(17, $user->pet->age );

        $user->pet->weight = 20;

        $this->assertEquals(20, $user->pet->weight );

        $user->save();

        $user = User::id($id);

        $this->assertEquals(20, $user->pet->weight );

    }

    public function testEmbeds()
    {
        $user = new User;
        $user->name = 'michael';
        $user->save();

        $id = $user->getId();

        $pet = new Pet();
        $pet->name = 'pet1';
        $pet->weight = 10;

        $pet2 = new Pet();
        $pet2->name = 'pet2';
        $pet2->weight = 15;

        $user->pets_fav = array($pet,$pet2);
        $user->save();

        $user = User::id($id);

        $this->assertEquals(2, $user->pets_fav->count() );

        $this->assertEquals('pet1', $user->pets_fav->get(0)->name );

        $user->pets_fav->remove(0);

        $user->save();

        $user = User::id($id);

        $this->assertEquals(1, $user->pets_fav->count() );
        $this->assertEquals('pet2', $user->pets_fav->get(0)->name );

        $user->save();

    }

    public function getInvalidModelIds() {
        return array(
            array(null),
            array('abcdef'),
            array(2),
            array(''),
        );
    }

    /**
     * @dataProvider getInvalidModelIds
     *
     * @param mixed $id
     */
    public function testIdReturnsNullForInvalidId($id)
    {
        $user = new User(array('name' => 'Brian'));
        $result = User::id($id);
        $this->assertNull($result);
    }
}
