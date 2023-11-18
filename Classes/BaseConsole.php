<?php

namespace Base;

class BaseConsole
{

  private $count;
  private $args = [];

  protected function setCount($count = 0): void{
    $this->count = $count;
  }

  protected function getCount(): int{
    return $this->count;
  }

  protected function setArgs($args = []): void{
    $this->args = $args;
  }

  protected function getArgs(): array{
    return $this->args;
  } 

  public function __construct($count, $args){
    $this->setCount($count);
    $this->setArgs($args); 
  }

  public function __invoke($command_list)
  {
    if($this->getCount() > 0 && array_key_exists($this->getArgs()[1], $command_list)){
      $console_class = 'App\Console\Handlers\\'.str_replace('/', '\\',$command_list[$this->args[1]]);
      $method = 'handle';
  
      $class = new $console_class($this->getCount(), $this->getArgs());
      
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
