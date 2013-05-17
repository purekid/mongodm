<?php

/**  Determine if use your local config file in place of this file   **/
$use_local_config = false;

/** The path of your config file , change to fit for your project.**/
$local_config_file = __DIR__."/../../../../application/config/local/mongodm.php";

if($use_local_config && file_exists($local_config_file)){
		$array = require $local_config_file;
		return $array;
}else{
	throw new Exception("File {$local_config_file} not exists!");
}


/**
 *  --------------------------------------------------------------
 *  | You can create a local config file with the content below. |
 *  --------------------------------------------------------------
 *  If environment variable 'APPLICATION_ENV' is defined 
 *  and your model $config is 'default',we use APPLICATION_ENV as the section name.
 */
return array(

   /* Configuration section name*/
	'default' => array(
		'connection' => array(
			'hostnames' => 'localhost',
			'database'  => 'default',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'development' => array(
		'connection' => array(
			'hostnames' => 'localhost',
			'database'  => 'development_db',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'testing' => array(
		'connection' => array(
			'hostnames' => 'localhost,192.168.1.2',
			'database'  => 'test_db',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'production' => array(
			'connection' => array(
				'hostnames' => 'localhost',
				'database'  => 'production_db',
// 				'username'  => '',
// 				'password'  => '',
			)
	)
);

