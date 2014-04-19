<?php

	error_reporting(E_ALL);

	define( 'ROOT', dirname(__FILE__));
	define( 'LIB', ROOT . '/lib');

	define( 'DEBUG',  in_array('local', explode('.', isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'aes.local' )) && true ); 

	define( 'DATABASE', 'MySQL');
	//define( 'DATABASE', 'SQLite');
	
    if(DATABASE == 'MySQL')
    {
        define( 'DBNAME', 'binbir');
        define( 'DBUSER', 'binbir');
        define( 'DBPASS', '');
        define( 'DBSERVER', 'localhost');
    } else
    if(DATABASE == 'SQLite')
    {
        define( 'DBNAME', ROOT . '/db/geolocation.sqlite3');
        define( 'DBUSER', null);
        define( 'DBPASS', null);
        define( 'DBSERVER', null);
    };

?>