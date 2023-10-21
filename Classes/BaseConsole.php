<?php

namespace Base;

class BaseConsole
{

  protected $count;
  protected $args = [];

  public function __construct($count, $args)
  {
    $this->count = $count;
    $this->args = $args;
  } 

  public function __invoke($command_list)
  {
    if($this->count > 0 && array_key_exists($this->args[1], $command_list)){
      require_once('app/Console/Handlers/'.$command_list[$this->args[1]].'.php');
      $console_class = 'App\Console\Handlers\\'.str_replace('/', '\\',$command_list[$this->args[1]]);
      $method = 'handle';
  
      $class = new $console_class($this->count, $this->args);
      
      if(method_exists($class, $method)){
        $class->{ $method }();
        throw new \Exception('Done!');
      }
      else{
        throw new \Exception('Handler method not defined!');
      }
    }
    else{
      throw new \Exception('Command not found!');
    }
  }

}

?>
