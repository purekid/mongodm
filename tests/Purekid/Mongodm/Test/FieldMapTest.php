<?php

namespace Purekid\Mongodm\Test;

use Purekid\Mongodm\Collection;
use Purekid\Mongodm\Test\TestCase\PhactoryTestCase;
use Purekid\Mongodm\Test\Model\Pet;
use Purekid\Mongodm\Test\Model\Book;
use Purekid\Mongodm\Test\Model\User;

class FieldMapTest extends PhactoryTestCase {

    public function testSet()
    {
        $expected = 'field';

        $user = new User();
        $user->fieldMapping = $expected;
        $user->save();

        // Get raw data
        $userRaw = self::$db->getDB()->{User::$collection}->findOne(array('_id' => $user->getId()));
        $this->assertArrayHasKey('field_mapping', $userRaw, 'Field `fieldMapping` was not mapped correctly');
        $this->assertEquals($expected, $userRaw['field_mapping']);
    }

    public function testGet()
    {
        $expected = 'field';

        $user = new User();
        $user->fieldMapping = $expected;
        $user->save();
        $id = $user->getId();

        $userFind = User::id($id);
        $this->assertEquals($expected, $userFind->fieldMapping, 'Getting data field not found');
    }

    public function testEmbed()
    {
        $expected = 'field';

        $user = new User;
        $user->save();
        $id = $user->getId();

        $embed = new Pet();
        $user->fieldMappingEmbed = $embed;
        $user->save();
        $user->fieldMappingEmbed->fieldMappingEmbed = $expected;
        $user->save();
        $this->assertEquals($expected, $user->fieldMappingEmbed->fieldMappingEmbed);

        $user = User::id($id);
        $this->assertEquals($expected, $user->fieldMappingEmbed->fieldMappingEmbed);

        $userRaw = self::$db->getDB()->{User::$collection}->findOne(array('_id' => $id));
        $this->assertArrayHasKey('field_mapping_embed', $userRaw, 'Field `fieldMappingEmbed` was not mapped correctly');
        $this->assertArrayHasKey('field_mapping_embed', $userRaw['field_mapping_embed'], 'Field `fieldMappingEmbed.fieldMappingEmbed` was not mapped correctly');
        $this->assertEquals($expected, $userRaw['field_mapping_embed']['field_mapping_embed']);
    }

    public function testEmbeds()
    {
        $expected = 'field';

        $user = new User;
        $user->save();
        $id = $user->getId();

        $pet = new Pet();
        $pet->fieldMappingEmbeds = $expected;

        $pet2 = new Pet();
        $pet2->fieldMappingEmbeds = $expected.'2';

        $user->fieldMappingEmbeds = array($pet,$pet2);
        $user->save();

        $user = User::id($id);
        $this->assertEquals(2, $user->fieldMappingEmbeds->count() );
        $this->assertEquals($expected, $user->fieldMappingEmbeds->get(0)->fieldMappingEmbeds );

        $userRaw = self::$db->getDB()->{User::$collection}->findOne(array('_id' => $id));
        $this->assertArrayHasKey('field_mapping_embeds', $userRaw, 'Field `fieldMappingEmbeds` was not mapped correctly');
        $this->assertArrayHasKey('field_mapping_embeds', $userRaw['field_mapping_embeds'][0], 'Embeded 1 `fieldMappingEmbeds.0.fieldMappingEmbeds` was not mapped correctly');
        $this->assertEquals($expected, $userRaw['field_mapping_embeds'][0]['field_mapping_embeds']);
        $this->assertArrayHasKey('field_mapping_embeds', $userRaw['field_mapping_embeds'][1], 'Embeded 2 `fieldMappingEmbeds.1.fieldMappingEmbeds` was not mapped correctly');
        $this->assertEquals($expected.'2', $userRaw['field_mapping_embeds'][1]['field_mapping_embeds']);

    }
    
    public function testReference()
    {
        $expected = 'field';

        $user = new User();
        $user->save();
        $id = $user->getId();
        
        $ref = new Book();
        $ref->fieldMappingRef = $expected;
        $ref->fieldMappingRef = $expected; 
        $ref->save();
        
        $user->fieldMappingRef = $ref;
        $user->save();
        
        $user = User::id($id);
        $book_fav = $user->book_fav;
        
        $this->assertEquals($expected, $user->fieldMappingRef->fieldMappingRef, 'Reference was not mapped');
        $this->assertEquals($expected, $user->fieldMappingRef->fieldMappingRef, 'Reference\'s field was not mapped');

        $userRaw = self::$db->getDB()->{User::$collection}->findOne(array('_id' => $id));
        $bookRaw = self::$db->getDB()->{Book::$collection}->findOne(array('_id' => $ref->getId()));
        $this->assertArrayHasKey('field_mapping_ref', $userRaw, 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertArrayHasKey('$ref', $userRaw['field_mapping_ref'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertArrayHasKey('$id', $userRaw['field_mapping_ref'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals(Book::$collection, $userRaw['field_mapping_ref']['$ref'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($ref->getId(), $userRaw['field_mapping_ref']['$id'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($expected, $bookRaw['field_mapping_ref']);
    }
    
    public function testReferences()
    {
        $expected = 'field';
        
        $user = new User();
        $user->save();
        $id = $user->getId();
    
        $ref1 = new Book();
        $ref1->fieldMappingRefs = $expected;
        $ref1->save();
        
        $ref2 = new Book();
        $ref2->fieldMappingRefs = $expected.'2';
        $ref2->save();
    
        $user->fieldMappingRefs = Collection::make(array($ref1, $ref2));
        $user->save();
    
        $user = User::id($id);
        $refs = $user->fieldMappingRefs;
    
        $this->assertEquals(2, $refs->count());
        $this->assertEquals($expected, $refs->get((string) $ref1->getId() )->fieldMappingRefs);
        $this->assertEquals($expected.'2', $refs->get((string) $ref2->getId() )->fieldMappingRefs);

        $userRaw = self::$db->getDB()->{User::$collection}->findOne(array('_id' => $id));
        $book1Raw = self::$db->getDB()->{Book::$collection}->findOne(array('_id' => $ref1->getId()));
        $book2Raw = self::$db->getDB()->{Book::$collection}->findOne(array('_id' => $ref2->getId()));
        $this->assertArrayHasKey('field_mapping_refs', $userRaw, 'Field `fieldMappingRefs` was not mapped correctly');
        
        $this->assertArrayHasKey('$ref', $userRaw['field_mapping_refs'][0], 'Field `fieldMappingRefs.0` was not mapped correctly');
        $this->assertArrayHasKey('$id', $userRaw['field_mapping_refs'][0], 'Field `fieldMappingRefs.0` was not mapped correctly');
        $this->assertEquals(Book::$collection, $userRaw['field_mapping_refs'][0]['$ref'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($ref1->getId(), $userRaw['field_mapping_refs'][0]['$id'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($expected, $book1Raw['field_mapping_refs']);

        $this->assertArrayHasKey('$ref', $userRaw['field_mapping_refs'][1], 'Field `fieldMappingRefs.1` was not mapped correctly');
        $this->assertArrayHasKey('$id', $userRaw['field_mapping_refs'][1], 'Field `fieldMappingRefs.1` was not mapped correctly');
        $this->assertEquals(Book::$collection, $userRaw['field_mapping_refs'][1]['$ref'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($ref2->getId(), $userRaw['field_mapping_refs'][1]['$id'], 'Field `fieldMappingRef` was not mapped correctly');
        $this->assertEquals($expected.'2', $book2Raw['field_mapping_refs']);
    }

}