<?php

namespace Purekid\Mongodm\Test\Model;


class Pupil extends Student
{

 	protected static $attrs = array(
 				
 		'class' => array('default'=>"A",'type'=>'string'),
 			
	);
 	
}

