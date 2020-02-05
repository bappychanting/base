<?php

try {
		// Include autoload
	include("vendor/autoload.php");

		// Include project configurations
	if(file_exists("env.php") && is_readable("env.php")) {
		include("env.php");
	}
	else{
		throw new Exception('Environment configuration file not found! Please create a copy of the &quot;env.exmaple.php&quot; file in the root folder and rename it to &quot;env.php&quot;.');
	}

		// Check if database migration
	if($_SERVER['REQUEST_URI'] == '/database_migration'){
		echo Base\Migration::migrationView();
	}
	elseif($_SERVER['REQUEST_URI'] == '/execute_queries'){
		// header('Content-type: application/json');
		$database_files = glob("database/*.php");
		$messages = Base\Migration::executeQueries($database_files);
		echo json_encode($messages);
	}

}
catch (Exception $e) {
    logger('ERROR: '.$e->getMessage());
    die(json_encode(['status'=>401, 'reason'=>$e->getMessage()]));
}
finally{
	ob_end_flush();
}
	
?>