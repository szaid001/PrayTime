<?php
/*
	
*/
	include( dirname(__FILE__) . '/../init.php' );


$router = new Router();

// auth
$router->post('/api/auth/login', 'login');
$router->get('/api/auth/current', 'currentUser');


// Place
$router->get('/api/location', 'searchLocation');

$router->get('/api/geolocation', 'searchGeoLocation');

// run
$router->run() or die('unsupported request!');

// Authentication
function login($values)
{
	global $User;

	$username = isset($values['username']) ? $values['username'] : '';
	$password = isset($values['password']) ? $values['password'] : '';
	$remember = isset($values['remember']);
	
	$User->login($username, $password, $remember) or die('username or password incorrect!');
	Response::json( $User->getUserInfo() );
}

function currentUser()
{
	global $User;
	Response::json( array('id'=>0) );
	//Response::json( $User->getUserInfo() );
}

// Place

function searchLocation($params)
{
	/*
	global $DB;
	$parent = isset($params['parent']) ? $params['parent'] : 0;
	$data = $DB->fetchRows(sprintf('select * from place where parent=%d order by code, name;', $parent));
	*/
	$data = array(array('Text'=>'Turkiye', 'Value'=>'Turkiye'), array('Text'=>'ABD', 'Value'=>'ABD'));
	Response::json( $data );
}

/*
Select
	*,
	sqrt(pow(latitude - (40.858041), 2) + pow(longitude - (-74.117177), 2)) as distance
from geolocation
where  (sqrt(pow(latitude - (40.858041), 2) + pow(longitude - (-74.117177), 2)) < 0.300000)
order by distance
limit 1 offset 0;
*/
function searchGeoLocation($param)
{
	global $DB;
	
	$SQL['field'][] = '*';
	$SQL['table'][] = 'geolocation';
	
	$lat = isset($param['latitude']) ? $param['latitude'] : 0;
	$lon = isset($param['longitude']) ? $param['longitude'] : 0;
	$limit = isset($param['limit']) ? $param['limit'] : 1;
	
	$distance = 0.3;

	if($lat && $lon && $distance)
	{
		$SQL['field'][] = sprintf('sqrt(pow(latitude - (%f), 2)+pow(longitude - (%f), 2)) as distance', $lat, $lon);
		$SQL['where'][] = sprintf('sqrt(pow(latitude - (%f), 2)+pow(longitude - (%f), 2)) < %f', $lat, $lon, $distance);
		$SQL['order'][] = 'distance';
	}
	
	$sql = $DB->selectSQL($SQL, $limit); //die($sql);
	$data = $DB->fetchRows($sql);
	Response::json( $data );
}



?>