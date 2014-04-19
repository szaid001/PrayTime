<?php
	
	include( dirname(__FILE__) . '/config.php');
				
	date_default_timezone_set('America/New_York'); 
	
	if(isset($_SERVER['HTTP_ORIGIN']))
	{
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		header('Access-Control-Allow-Headers: Token');
	}
				
	spl_autoload_register(function($classname){
		if( file_exists( $file = LIB . '/' . $classname . '.php' ) )
			include_once($file);
	});

	ErrorHandler::create();	
	
	try
	{
		$Database = DATABASE;
		$DB = new $Database( DBNAME, DBUSER, DBPASS, DBSERVER );
        
        if($DB->Database == Database::SQLite)
        {
            $DB->exec('SELECT load_extension(\'/opt/local/lib/libsqlitefunctions\');');
        }
        
	}
	catch( Exception $e )
	{
		if(DEBUG) echo $e->getMessage();
		die('Database connection failed!');
	};

	//$User = new User( $DB );

?>