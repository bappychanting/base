<?php

namespace Base;

class Migration
{

  private static $app_name = APP_NAME;
  private static $app_env = APP_ENV;
  private static $db_host = DB_HOST;
  private static $db_username = DB_USERNAME;
  private static $db_password = DB_PASSWORD;
  private static $db_database = DB_DATABASE;

  // output string
  private static function output($message){
    echo $message; echo ("\n");
  }

  // Execute Database Queries
  public static function executeQueries($reset_migration, $database_files = array())
  {

    if(self::$app_env == 'dev'){

      $con=mysqli_connect(self::$db_host, self::$db_username, self::$db_password, self::$db_database);

      // Check connection
      if (mysqli_connect_errno()){
        self::output("Error: Failed to connect to MySQL! " . mysqli_connect_error());
      }

      if($reset_migration == "reset"){
        $status = mysqli_query($con, "DROP TABLE IF EXISTS `migrations`");
        if($status){
          self::output('Success: Existing Migration table has been removed!');
        }
      }

      $check_migration = mysqli_query($con, 'SHOW TABLES LIKE "migrations"');

      if(mysqli_num_rows($check_migration) == 0){
        $status = mysqli_query($con, "CREATE TABLE `migrations` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `batch` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        )");
        if($status){
          self::output('Success: Migration table has been created!');
        }
      }

      $migrations = mysqli_query($con, "SELECT * FROM `migrations`");
      $all_keys = array();
      while ($migration = mysqli_fetch_assoc($migrations))
      {
        array_push($all_keys, $migration['migration']);
      }

      // Include Queries
      foreach ($database_files as $files){

        $queries = include $files;

        foreach ($queries as $key=>$value){
          if(in_array($key, $all_keys)){
            array_push($messages, ['type' => "warning", 'text' => 'Warning: Migration `'.ucwords(str_replace("_", " ", $key)).'` already exists! Migration skipped.']);
          }
          else{
            // Execute Query
            $status = mysqli_query($con, $value);
            if($status){
              mysqli_query($con, 'INSERT INTO migrations (migration, batch) VALUES ("'.$key.'", '.time().')');
              self::output('Success: Query `'.ucwords(str_replace("_", " ", $key)).'` executed successfully!');
            }
            else{
              self::output('Error: Query `'.ucwords(str_replace("_", " ", $key)).'` failed! Reason: '.mysqli_error($con));
            }   
          }
        }
      }

      mysqli_close($con);
    }
    else{
      self::output('Error: Mismatched values in project environment configuration!');
    }
       
    die();

  }

}

?>
