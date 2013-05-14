<?php

namespace Purekid\Mongodm\Test\Model;


class Student extends User
{

	static $collection = "student";
 	public static $config = 'test';
	
 	protected static $attrs = array(
 				
 		'grade' => array('type'=>'string'),
 			
	);
	
}