<?php

namespace Purekid\Mongodm\Test\Model;


class Pupil extends Student
{
	static $collection = "pupil";
	
 	protected static $attrs = array(
 				
 		'class' => array('default'=>"A",'type'=>'string'),
 			
	);
 	
}

