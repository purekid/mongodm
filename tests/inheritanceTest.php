<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Test\Model\Pupil;
use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\Student;

class InheritanceTest extends PhactoryTestCase
{
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
        $student->name = "michael_pupil";
        $student->save();
        $this->assertEquals("A", $student->class);

    }

    public function testRetrieve()
    {

        $pupils =  Pupil::all();
        $pupil = $pupils->get(0);
        $this->assertTrue($pupil instanceof Pupil);
    }

    public function testRelation1TO1()
    {
    $user = new Student(array("name"=>"John"));
    $user->save();
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

    public function testDrop()
    {
        $pupil = Pupil::one();
        $this->assertNotEmpty($pupil);
        Pupil::drop();
        $pupil = Pupil::one();
        $this->assertEmpty($pupil);

    }

}
