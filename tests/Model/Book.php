<?php

namespace Purekid\Mongodm\Test\Model;

class Book extends Base
{

	static $collection = "book";

    protected static $attrs = array(
      
        'fieldMappingRef' => array('type'=>'string', 'field' => 'field_mapping_ref'),
        'fieldMappingRefs' => array('type'=>'string', 'field' => 'field_mapping_refs'),

    );
	
}