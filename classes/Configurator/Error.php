<?php

declare(strict_types=1);

namespace EPConf;

class Configurator_Error
{
   /**
    * @var string
    */
    protected $message;
    
   /**
    * @var int
    */
    protected $code;
    
    public function __construct(string $message, int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }
    
    public function getMessage() : string
    {
        return $this->message;
    }
    
    public function getCode() : int
    {
        return $this->code;
    }
}
