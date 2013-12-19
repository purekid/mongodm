<?php

namespace Purekid\Mongodm\Test\Model;

class Pet extends Base
{

	static $collection = "pet";

    protected static $attrs = array(

        'name' => array('type'=>'string' ,'default'=>'Puppy'),

        'fieldMappingEmbed' => array('type'=>'string', 'field' => 'field_mapping_embed'),
        'fieldMappingEmbeds' => array('type'=>'string', 'field' => 'field_mapping_embeds'),

    );

}