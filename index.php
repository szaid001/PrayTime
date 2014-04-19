<?php
/*
	istenen url bulunamadigi zaman server requesti bu scripte redirect eder.
	
	Bu script application'u paketler ve clienta yukler.
	
	ulasilmak istenen filename application name olarak kabul edilir.

	url bu sekilde geldiginde /app/bogus/bogus/schedule.js
	schedule icindeki schedule.html, schedule.js ile paketlenir ve yuklenir.
	
	
	/app/app1.js
	
	/module/module1.js
	
	/css/style.css
	
	
	
*/

	$File = trim( isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '', '/' ); //die($File);
	
	$A = explode('/', $File);
	$E = pathinfo($File, PATHINFO_EXTENSION);
	
	switch( $where = array_shift( $A ))
	{
		case 'api':
			include('api/index.php');
			break;

		case 'app':
		case 'module':
			switch( $E )
			{
				case 'js':
				
					$application = pathinfo($File, PATHINFO_FILENAME);
					$js = file_get_contents("$where/$application/$application.js");
					$html = file_get_contents("$where/$application/$application.html");
					$html = preg_replace('/[\n\r\t]+/', '', $html);

					$data 	= str_replace('%HTML%', addslashes($html), $js);

					header('Content-type: text/javascript');
					die( $data );

					break;
					
				default:
					die('use api request instead of app request!');
					
			}
			break;

		case 'css':
			if($E == 'css')
			{
				$fp = pathinfo($File);	
				$file = $fp['dirname'] . '/' . $fp['filename'] . '.less';
				
				if(file_exists($file))
				{
					include('config.php');
					include( LIB . '/lessc.php');
					
					header('Content-type: text/css');
				
					$css = new lessc();
					die( $css->compileFile($file) );
				}
				
			}
			else 
			{
				header('HTTP/1.1 404 Not Found');
				die('file not found! ' . $File);
			}
			break;	
			
		case 'js':
		
			if($File == 'js/all.js')
			{
			
				header('Content-type: text/javascript');
			
				$files = array(
					'js/jquery-1.9.1-min.js',
					'js/underscore-1.4.4-min.js',
					'js/backbone-0.9.10-min.js',
					'js/less-1.3.3.min.js',
					'js/modernizr-2.6.2.custom.js',
					'js/PrayTimes.js',
					'js/php.js/date.js',
					'js/php.js/strtotime.js'
				);
			
				foreach($files as $file){
					echo str_repeat("\n", 3);
					echo "/* FILE: $file */\n";
					echo file_exists($file) ? file_get_contents( $file ) : "/* FILE NOT FOUND : $file */\n";
				}
						
				die();
				
			}
			break;

		default:
			//print_r( explode('/', $File) );
			die(file_get_contents('index.html'));
				
	}


?>