<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Test\Model\User;

class MongoDBTest extends PhactoryTestCase
{

    public function testAggregate()
    {
        $time = microtime();
        $user = new User(array("time" => $time,"name"=>"John", "money" => 100));
        $user->save();

        $user1 = new User(array("time" => $time,"name"=>"John1", "money" => 100));
        $user1->save();

        $user2 = new User(array("time" => $time,"name"=>"John2", "money" => 100));
        $user2->save();

        $user3 = new User(array("time" => $time,"name"=>"John3", "money" => 100));
        $user3->save();

        $user4 = new User(array("age" => 40,"name"=>"John4", "money" => 100));
        $user4->save();

        $user5 = new User(array("time" => $time,"name"=>"John6", "money" => 100));
        $user5->save();

        $user6 = new User(array("time" => $time,"name"=>"John6", "money" => 100));
        $user6->save();

        $result = User::aggregate(array(
            array(
                '$match' => array(
                    "time" => $time
                )
            )
        ));
        $this->assertCount(6, $result['result']);
    }

}
