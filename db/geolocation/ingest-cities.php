#!/opt/local/bin/php55 -q
<?php 

    /*
        http://www.geonames.org/
        
        History
            11/13/2013, Just Created
     */

    define ( 'ROOT', dirname(dirname(__FILE__)) );
	define ( 'LIB', ROOT . '/../lib' );

    if(false)
    {
        define( 'DATABASE',	'MySQL');
        define( 'DBNAME',	'binbir');
        define( 'DBUSER',	'binbir');
        define( 'DBPASS',	'bin1');
    } 
    else 
    {
        define( 'DATABASE',	'SQLite');
        define( 'DBNAME',	ROOT . '/../db/geolocation.sqlite3');
        define( 'DBUSER',	null);
        define( 'DBPASS',	null);
    }

    define( 'DBSERVER', 'localhost');

	define( 'USEGEOID', true ); // record ID olarak geonameid kullanilacak.
	define( 'STARTOVER', true ); 
	define( 'DEBUG', true ); 
	 
	spl_autoload_register(function($classname){
		if( file_exists( $file = LIB . '/' . $classname . '.php' ) )
			include_once($file);
	});
	 
	ErrorHandler::create();	
	
	$Database = DATABASE;
	$DB = new $Database( DBNAME, DBUSER, DBPASS, DBSERVER );
	$DS = null;
	$Error = array();
	
	function trimmer(&$value, $key)
	{
		$value = trim($value);
	}
	
	function hasValue($value){ die('r u using hasValue function?!'); return trim($value)!=''; }
	
	function getParent($path)
	{
		global $DB;
		//$a = explode('.', $path);
		//array_pop($a);
		//$sql = sprintf('select * from geolocation where path=\'%s\';', implode('.', $a));
		$sql = sprintf('select * from geolocation where concat_ws(\'.\', path, code)=\'%s\';', $path);
		//echo "getParent: $path, $sql\n";
		return $DB->fetchRow($sql);
	}
	
	function getParentId($path)
	{	die('do not use it!');
		global $DB;
		$a = explode('.', $path);
		array_pop($a);
		return $DB->getValue(sprintf('select id from geolocation where path=\'%s\';', implode('.', $a)));
	}
	
	function initialize($file)
	{
		global $DB;
        $DB->exec('drop table if exists geolocation;');
        $sql = implode(@file($file)); 
		STARTOVER && $DB->exec( $sql );
		$DS = new DBTable($DB, 'geolocation', false);
		USEGEOID || $DS->setKeyFields('parent,code');
		//$DS->disableFields('id');
		return $DS;
	}
	
	function importCountries($file)
	{	echo time()." - importCountries($file)...\n";
		global $DS, $Error;
		$fields = explode(",", 'code,iso3,isonumeric,fips,name,capital,area,population,continent,tld,currencycode,currencyname,phone,postalcodeformat,postalcoderegex,languages,geonameid,neighbours,equivalentfipscode');
		$lines = @file($file);
		foreach($lines as $line)
		{
			if($line[0]=='#') continue;
			$data = array_combine($fields, explode("\t", $line));
			USEGEOID && $data['id']=$data['geonameid']; 
			//$data['path']=$data['code'];
			$data['parent']=0;
			array_walk($data, 'trimmer'); 
			//print_r($data);
			$data['info']=json_encode($data, JSON_NUMERIC_CHECK);
			$DS->resetKeyValues($data);
			$DS->resetValues($data);
			try { STARTOVER ? $DS->insert() : $DS->save(); } catch(Exception $e){  $Error[] = array('message'=>$e->getMessage() . "\n\t" . trim($line), 'e'=>$e ); };
		}	
	}

	function importPlace1($file)
	{	echo time()." - importPlace1($file)...\n";
		global $DB, $DS, $Error;
		$fields = explode(",", 'path,nameutf,name,geonameid');
		$lines = @file($file);
		foreach($lines as $line)
		{
			if($line[0]=='#') continue;
			$data = array_combine($fields, explode("\t", $line));
			USEGEOID && $data['id']=$data['geonameid']; 
			list($data['path'], $data['code']) = explode('.', $data['path']);
			/*
			if($parent = getParent($data['path']))
			{
				$data['parent'] = $parent['id'];
				$data['namespace'] = ($parent['namespace']?$parent['namespace'] . '/' : '') . $parent['name'];
			};
			*/
			array_walk($data, 'trimmer');
			$data['info']=json_encode($data, JSON_NUMERIC_CHECK);
			//print_r($data); 
			$DS->resetKeyValues($data);
			$DS->resetValues($data);
			try { STARTOVER ? $DS->insert() : $DS->save(); } catch(Exception $e){  $Error[] = array('message'=>$e->getMessage() . "\n\t" . trim($line), 'e'=>$e ); };
		}	
	}

	function importPlace2($file)
	{	echo time()." - importPlace2($file)...\n";
		global $DB, $DS, $Error;
		$fields = explode(",", 'path,nameutf,name,geonameid');
		$lines = @file($file);
		foreach($lines as $line)
		{
			if($line[0]=='#') continue;
			$data = array_combine($fields, explode("\t", $line));
			USEGEOID && $data['id']=$data['geonameid'];
				 
			list($country, $place, $data['code']) = explode('.', $data['path']);
			$data['path']=$country.'.'.$place;
			/*
			if($parent = getParent($data['path']))
			{
				$data['parent'] = $parent['id'];
				$data['namespace'] = ($parent['namespace']?$parent['namespace'] . '/' : '') . $parent['name'];
			};
			*/
			array_walk($data, 'trimmer');
			$data['info']=json_encode($data, JSON_NUMERIC_CHECK);
			//print_r($data); 
			$DS->resetKeyValues($data);
			$DS->resetValues($data);
			try { STARTOVER ? $DS->insert() : $DS->save(); } catch(Exception $e){  $Error[] = array('message'=>$e->getMessage() . "\n\t" . trim($line), 'e'=>$e ); };
		}	
	}

	function importCities($file)
	{	echo time()." - importCities($file)...\n";

		/*	
		geonameid         : integer id of record in geonames database
		name              : name of geographical point (utf8) varchar(200)
		asciiname         : name of geographical point in plain ascii characters, varchar(200)
		alternatenames    : alternatenames, comma separated varchar(5000)
		latitude          : latitude in decimal degrees (wgs84)
		longitude         : longitude in decimal degrees (wgs84)
		feature class     : see http://www.geonames.org/export/codes.html, char(1)
		feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
		country code      : ISO-3166 2-letter country code, 2 characters
		cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
		admin1 code       : fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
		admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) 
		admin3 code       : code for third level administrative division, varchar(20)
		admin4 code       : code for fourth level administrative division, varchar(20)
		population        : bigint (8 byte int) 
		elevation         : in meters, integer
		dem               : digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
		timezone          : the timezone id (see file timeZone.txt) varchar(40)
		modification date 	
		*/	

		global $DB, $DS, $Error;
		$fields = explode(",", 'geonameid,nameutf,name,names,latitude,longitude,featureclass,featurecode,countrycode,cc2,place1,place2,place3,place4,population,elevation,dem,timezone,modificationdate');
		$lines = @file($file);
		foreach($lines as $line)
		{
			if($line[0]=='#') continue;
			$data = array_combine($fields, explode("\t", $line));
			USEGEOID && $data['id']=$data['geonameid']; 
			//$data['code'] = $data['geonameid'];
			$data['path'] = implode('.', 
				array_filter( 
					array($data['countrycode'], $data['place1']?$data['place1']:'??', $data['place2']?$data['place2']:'??', $data['place3'], $data['place4']), 
					function($value){ return trim($value)!=''; }
				)
			);
			/*			
			if($parent = getParent($data['path']))
			{
				$data['parent'] = $parent['id'];
				$data['namespace'] = ($parent['namespace']?$parent['namespace'] . '/' : '') . $parent['name'];
			};
			*/
			array_walk($data, 'trimmer');
			$data['info']=json_encode($data);
			//print_r($data); 
			$DS->resetKeyValues($data);
			$DS->resetValues($data);
			try { STARTOVER ? $DS->insert() : $DS->save(); } catch(Exception $e){  $Error[] = array('message'=>$e->getMessage() . "\n\t" . $line . "\n\t" . $data['info'], 'e'=>$e ); };
		}	
	}

	function updateCitiesNameSpace()
	{	echo time()." - updateCitiesNameSpace()...\n";
		global $DB;
		$sql = "update geolocation c "; 
		$sql.= "inner join geolocation p on concat_ws('.', p.path, p.code) = c.path "; 
		$sql.= "set c.parent=p.id, c.namespace=concat_ws('/', p.namespace, p.name), c.namespaceutf=concat_ws('/', p.namespaceutf, p.nameutf) ";
		$sql.= "where c.parent is null";
		echo "$sql\n";		
		$DB->exec($sql);
	} 

    $SQLfile = DATABASE == 'SQLite' ? '/initialize.lite.sql' : '/initialize.my.sql';
	$DS = initialize( dirname(__FILE__) . $SQLfile);
	importCountries( dirname(__FILE__) . '/countryInfo.txt');
	//importPlace1( dirname(__FILE__) . '/admin1CodesASCII.txt');
	//importPlace2( dirname(__FILE__) . '/admin2Codes.txt');
	//importCities( dirname(__FILE__) . '/cities1000.txt');
	//updateCitiesNameSpace();
	
    echo "\n\n";

	foreach($Error as $e) {
		echo "\n\n---ERROR-------------\n";
		//print_r($e);
		echo $e['message'];
	};


	
	echo sprintf("%d records inserted, USEGEOID=%d, Errors: %d.\n", $DB->getValue('select count(*) from geolocation;'), USEGEOID, count($Error) );	
	
	
?>