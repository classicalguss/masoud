<?php

if (YII_DEBUG)
{
	// This is the database connection configuration.
	return array(
		//'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
		// uncomment the following lines to use a MySQL database
		
		'connectionString' => 'mysql:host=localhost;dbname=masoud_application',
		'emulatePrepare' => true,
		'username' => 'root',
		'password' => '',
		'charset' => 'utf8',
	);
}
else
{
	return array(
			//'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
			// uncomment the following lines to use a MySQL database
	
			'connectionString' => 'mysql:host=localhost;dbname=masoud_application',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'masamune88',
			'charset' => 'utf8',
	);
}