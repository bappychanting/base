<?php

namespace Base;

class Migration
{

  private static $app_name = APP_NAME;
  private static $app_env = APP_ENV;
  private static $app_key = APP_KEY;
  private static $db_host = DB_HOST;
  private static $db_username = DB_USERNAME;
  private static $db_password = DB_PASSWORD;
  private static $db_database = DB_DATABASE;

  // Execute Database Queries
  public static function executeQueries($database_files = array())
  {

    $messages = array();

    if(self::$app_env == 'dev' && !empty($_POST['app-key']) && self::$app_key == $_POST['app-key']){

      $con=mysqli_connect(self::$db_host, self::$db_username, self::$db_password, self::$db_database);

    // Check connection
      if (mysqli_connect_errno()){
        array_push($messages, ['type' => "danger", 'text' => "Error: Failed to connect to MySQL! " . mysqli_connect_error()]);
      }

      if(isset($_POST['reset_migration']) && $_POST['reset_migration'] == "reset"){
        $status = mysqli_query($con, "DROP TABLE IF EXISTS `migrations`");
        if($status){
          array_push($messages, ['type' => "success", 'text' => 'Success: Existing Migration table has been removed!']);
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
          array_push($messages, ['type' => "success", 'text' => 'Success: Migration table has been created!']);
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
              array_push($messages, ['type' => "success", 'text' => 'Success: Query `'.ucwords(str_replace("_", " ", $key)).'` executed successfully!']);
            }
            else{
              array_push($messages, ['type' => "danger", 'text' => 'Error: Query `'.ucwords(str_replace("_", " ", $key)).'` failed! Reason: '.mysqli_error($con)]);
            }   
          }
        }
      }

      mysqli_close($con);
    }
    else{
      array_push($messages, ['type' => "danger", 'text' => 'Error: Mismatched values in project environment configuration!']);
    }

    return $messages;

  }

  // Generate Views
  public static function migrationView($action_url='')
  {
  // Declaring app name
    $app_name = self::$app_name;

  // Styles
    $styles = '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">';
    $styles .= '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">';

  // Scripts
    $scripts = '<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>';
    $scripts .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>';
    $scripts .= '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>';
    $scripts .= '<script src="https://malsup.github.io/jquery.form.js"></script>';

  // Feedback from server
    $feedback = '';
    if(self::$app_env != 'dev'){
      $feedback = '<h4 class="text-muted"><i class="far fa-frown pr-2"></i>Oops..</h4><p class="text-danger">Migration is unavialable at the moment!</p>';
    }
    else{
      $feedback .= '<form class="mb-5 mx-5 execute-form" method="post" action="'.$action_url.'">';
      $feedback .= '<div class="form-group">';
      $feedback .= '<label for="key">Please enter project execution key to continue..</label>';
      $feedback .= '<input type="password" class="form-control" id="key" name="app-key" placeholder="KEY" pattern=".{3,}" required title="3 characters minimum">';
      $feedback .= '<small id="key" class="form-text text-muted">Check out project configuration file to find out your key!</small>';
      $feedback .= '</div>';
      $feedback .= '<div class="custom-control custom-checkbox my-3">';
      $feedback .= '<input type="checkbox" class="custom-control-input" id="reset_migration" name="reset_migration" value="reset">';
      $feedback .= '<label class="custom-control-label" for="reset_migration">Reset Migration Table</label>';
      $feedback .= '<small id="reset_migration" class="form-text text-muted">By default migration only executes newly added queries ! Check this box it if you want all queries executed!</small>';
      $feedback .= '</div>';
      $feedback .= '<button type="submit" class="btn btn-primary text-uppercase">Proceed<i class="fas fa-angle-double-right pl-2"></i></button>';
      $feedback .= '</form>';
    }

    $html = 
<<<EOD
    
    <!DOCTYPE html>
    <html lang="en">

    <!-- Header -->
    <head>
    <!-- Favicon-->
    <link rel="icon" href="resources/assets/img/favicon.png">
    <title>Database Migration || $app_name</title>
    
    <!-- CSS-->
    $styles

    <style>
    body {
      background-color: #f2f2f2;
    }
    .form-control {;
      text-align:center;
    }
    input:focus::-webkit-input-placeholder {
      opacity: 0;
    }
    a:link {
      text-decoration: none;
    }
    .brand {  
      position:absolute;
      bottom:0px;
      right:25%;
      left:50%;
    }
    .custom-control-label:before{
      background-color: #ffb3b3;
    }
    .custom-checkbox .custom-control-input:checked~.custom-control-label::before{
      background-color: #00b300;
    }
    </style>

    </head>
    <!-- #ENDS# Header -->

    <body>
    <div class="my-5 mx-5" align="center">
    <h1 class="mb-5 text-secondary">$app_name</h1>
    <h3 class="text-info mb-3">Welcome to Migration!</h3> 
    <div id="feedback" class="my-5">$feedback</div>
    </div>    

    <p class="small brand">
    <a href='https://www.codecubeit.com/' rel='nofollow' class='text-muted'>CodeCube IT Solutions</a>
    </p>           

    <!-- JQuery -->
    $scripts

    <script>    

      // Execute Queries
      (function() {
        $('.execute-form').ajaxForm({
          beforeSend: function() {
            $('#feedback').html('<p class="text-info">Database migration in progress...</p>');
            },
            uploadProgress: function(event, position, total, percentComplete) {
              percentVal = percentComplete + '%';
              $('#feedback').html('<p class="text-info">Database migration in progress...</p><div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '+percentVal+'" aria-valuenow="'+percentComplete+'" aria-valuemin="0" aria-valuemax="100">'+percentVal+'</div></div>');
            },
            success: function() {
              $('#feedback').html('<p class="text-success">Migration Complete!</p><div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100</div></div>');    
            },
            error: function() {
              $('#feedback').empty().append('<p class="text-danger"><i class="fa fa-warning pr-2"></i>Something went wrong in the server! Please wait until the page refreshes..</p><div class="progress"><div class="progress-bar bg-danger" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div></div>').fadeIn("slow");
              setTimeout(function() {
                location.reload();
              }, 1000);
            },
            complete: function(xhr) {
              $(".brand").remove();
              $('#feedback').append("<h5 class='my-5 text-secondary'><i class='far fa-clock pr-2'></i>Waiting for return messages...</h5>");
              var time = 1000;
              var message = JSON.parse(xhr.responseText);
              for( var i = 0; i<message.length; i++){
                var info = $("<p />");
                info.attr('class',"text-"+message[i]['type']);
                info.html('<i class="far fa-hand-point-right pr-2"></i>'+message[i]['text']);
                info.hide();
                $('#feedback').append(info);
                time += 100;
                info.delay(time).fadeIn('fast');
              };
              $('#feedback').append("<p class='small'><a href='https://www.codecubeit.com/' rel='nofollow' class='text-muted'>CodeCube IT Solutions</a></p>");
            }
          }); 
        })();

    </script>

    </html>
EOD;
    return $html;
  }

}

?>
