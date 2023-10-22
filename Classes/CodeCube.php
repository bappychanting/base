<?php

namespace Base;

class CodeCube
{
    private static $execute;

	private function __construct($config_files, $argc, $argv) 
	{
        try{
            // Checking missing configuration files
            foreach ($config_files as $file)
                if(!file_exists($file))  throw new \Exception('Essential project configuration file missing: '.str_replace('/', '&#47;',$file));
        
            // Include environment configuration files
            $env_array = include($config_files['env']);
            foreach($env_array as $env=>$value) define($env, $value);
        
            // Call Commands
            if(isset($argc) && $argc > 0){
                $console = new BaseConsole($argc, $argv);
                $console(include($config_files['commands']));
            }
        
            // Set default project routes
            $default = include($config_files['default']);
        
            // Create route url string
            $route_url = ltrim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
        
            // Checking if api url
            if(strpos($route_url, $default['api_url'].'/') === 0)
            {
                // Include api routes
                $routes = include($config_files['api_routes']); 
        
                // Rewrite URL
                $route_url = empty(substr($route_url, strlen($default['api_url'].'/'))) ? '404' : substr($route_url, strlen($default['api_url'].'/'));
            }
            else{
                // Include project application configuration files and setting up
                $config = include($config_files['app']);
                serverSetup($config);
        
                // Starting session
                session_start();
        
                // Include Web Routes
                $routes = include($config_files['web_routes']);
        
                // Sanitizing url and incoming parameters
                $route_url = sanitize($route_url, $routes);
            }
        
            // Call route
            if(empty($route_url))
                call($default['landing']);
            elseif(empty($routes[$route_url]))
                call($default['error']);
            else
                call($routes[$route_url]);
        
            // Log last occured error
            if(!empty(error_get_last()))    logger('ERROR: '.error_get_last()['message'].' in '.error_get_last()['file'].' in '.error_get_last()['line']);
        }
        catch (\Exception $e){
            die(json_encode(['status'=>$e->getCode(), 'reason'=>$e->getMessage()]));
        }
	}

    public static function start($config_files, $argc, $argv) 
	{
        if(!isset(self::$execute)){
            self::$execute = new CodeCube($config_files, $argc, $argv);
         }
         return self::$execute;
	}

}

?>