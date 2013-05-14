<?php

namespace Purekid\Mongodm\Test\Model;


class Pupil extends User
{

 	protected static $attrs = array(
 				
 		'class' => array('default'=>"A",'type'=>'string'),
 			
	);
	
}