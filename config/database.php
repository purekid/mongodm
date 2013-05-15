<?php

/**
 *  If environment variable 'APPLICATION_ENV' is defined 
 *  and your model $config is 'default',we use APPLICATION_ENV as the section name.
 */
return array(

   /* Configuration section name*/
	'default' => array(
		'connection' => array(
			'hostnames' => 'localhost',
			'hostnames' => 'localhost',
			'database'  => 'default',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'development' => array(
		'connection' => array(
			'hostnames' => 'localhost',
			'database'  => 'development',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'testing' => array(
		'connection' => array(
			'hostnames' => 'localhost',
			'database'  => 'test',
// 			'username'  => '',
// 			'password'  => '',
		)
	),
	'production' => array(
			'connection' => array(
				'hostnames' => 'localhost,192.168.1.2',
				'database'  => 'production',
// 				'username'  => '',
// 				'password'  => '',
			)
	)
);

