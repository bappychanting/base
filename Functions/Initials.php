<?php

	// Setting up
function serverSetup($config=array())
{
    if($config['update_session_cookie_settings'] == 'yes'){
        ini_set('session.gc_maxlifetime', strtotime($config['auth_time'], 0));
        session_set_cookie_params(strtotime($config['auth_time'], 0));
    }
}

	// Fucntion for generating log
function logger($log_msg = '')
{
	if(file_exists('config/app.php')){
		$config = include('config/app.php');
		if($config['auto_logging'] == 'on'){
			$log_filename = "storage/logs";
			if (!file_exists($log_filename)) 
			{
				mkdir($log_filename, 0777, true);
			}
			$log_file_data = $log_filename.'/log-' . date('Y-m-d') . '.log';
			if(is_array($log_msg)){
				$log_msg = json_encode($log_msg);
			}
			file_put_contents($log_file_data, '['.date('Y-m-d H:i:s').'] '.$log_msg . "\n", FILE_APPEND);
		}
	}
}

	// Fucntion for getting locale
function locale($loc_file, $loc_key, $words= array())
{
	if(file_exists('config/app.php')){
		$config = include('config/app.php');
		$_file = 'resources/locale/'.$config['locale'].'/'.$loc_file.'.php';
		if(file_exists($_file)){
			$locale = include($_file);
			$string = $locale[$loc_key];
			if(!empty($words)){
				foreach ($words as $key => $value) {
					$string = str_replace(':'.$key, $value, $string);
				}
			}
			return $string;
		}
	}
	return '';
}

	// Get Field Data
function getTokenData()
{
	$token_data = array();
	if(isset($_SESSION['processing_token'])){
		$token_data = $_SESSION['tokens'][$_SESSION['processing_token']];
	}
	else{
		$tokens = $_SESSION['tokens'];
		if(count($tokens) > 1){
			usort($tokens, function($a, $b) {
				return $a['time'] <=> $b['time'];
			});
		}
		$token_data = end($tokens);
	}
	return $token_data;
}

  	// Errors setter
function setErrors($errors)
{
	$token_data = getTokenData();
	$_SESSION['tokens'][$token_data['csrf_token']]['errors'] = $errors; 
}

	// Errors getter
function getErrors()
{
	$token_data = getTokenData();
	return $_SESSION['tokens'][$token_data['csrf_token']]['errors'];
}

  	// get return url
function back()
{
	$token_data = getTokenData();
	return ltrim($token_data['url'], '/');
}

	// Sanitizing parameters
function sanitize($route_url='', $routes=[])
{
	$headers = apache_request_headers();

		// Check if route is a sweet url 
	if(!empty($route_url) && !array_key_exists($route_url, $routes)){
		$url_keywords = explode("/", $route_url);
		foreach($routes as $route=>$controller){
			if(strpos($route, '{') !== false && strpos($route, '}') !== false){
				$route_keywords = explode("/", $route);
				if(count($url_keywords) == count($route_keywords)){
					$route_found = true;
					for($i=0; $i<count($route_keywords); $i++){
						if(strpos($route_keywords[$i], '{') == 0  && strpos($route_keywords[$i], '}') == (strlen($route_keywords[$i])-1))
							continue;
						if($route_keywords[$i] != $url_keywords[$i]){
							$route_found = false; break;
						}
					}
					if($route_found){
						foreach($route_keywords as $key=>$keyword){
							if( strpos($keyword, '{') == 0  && strpos($keyword, '}') == (strlen($keyword)-1) ){
								$_GET[substr($keyword, 1, -1)] = $url_keywords[$key];
							}
						}
						$route_url = $route;
						break;
					}
				}
			}
		}
	}
	
        // Sanitize url parameters
	if(!empty($_GET)){
		foreach ($_GET as $key => $value) {
			$key = preg_replace('/[^-a-zA-Z0-9_]/', '', $key);
			$value = preg_replace('/[^-a-zA-Z0-9_]/', '', $value);
			$_GET[$key] = $value;
		}
	}

		// Check and set post parameters
	if (!empty($_POST)) {
		if(!empty($headers['X-CSRF-TOKEN']) && array_key_exists($headers['X-CSRF-TOKEN'], $_SESSION['tokens'])){
			logger('Ajax call recieved to url: '.$_SERVER['REQUEST_URI'].'!');
		}
		elseif(!empty($_POST['_token']) && array_key_exists($_POST['_token'], $_SESSION['tokens'])){ 
			$_SESSION['processing_token'] = $_POST['_token']; 
			$_SESSION['tokens'][$_SESSION['processing_token']]['posts'] = $_POST; 
		}
		else{
			unset($_POST);
			throw new Exception('Token mismatch!');
		}
	}

	return $route_url;
}

	// Declaring controller method calling function
function call($route_url =''){
	
	$get_controller_action = explode("@", $route_url);

	$controller = $get_controller_action[0];

	$method = $get_controller_action[1];

	if(file_exists('app/Http/Controllers/'.$controller.'.php')){

		require_once('app/Http/Controllers/'.$controller.'.php');

		$controller_class = 'App\Http\Controllers\\'.str_replace('/', '\\', $controller);

		if(method_exists($controller_class , $method)) {		
			$class = new $controller_class();
			$class->{ $method }();
		}
		else{
			throw new Exception('Method &quot;'.$method.'&quot; not found in controller &quot;'.$controller_class.'&quot;!');
		}
	}
	else{
		throw new Exception('Controller &quot;'.$controller.'&quot; not found!');
	}
}

?>