<?php

namespace Ypf;

class Exception extends \Exception
{
     /**
    * @var string
    */
    private $_privateMessage = '';
    /**
    * Constructor
    *
    * @param string $publicMessage Private message, typically used for logging
    * @param string $privateMessage Public message, used to show to the user
    *
    * @return null
    */
    public function __construct($publicMessage, $privateMessage = '')
    {
        $this->_privateMessage = $privateMessage;
        parent::__construct($publicMessage);
    }
    /**
    * Gets the private message
    *
    * @return string
    */
    public function getPrivateMessage()
    {
        return $this->_privateMessage;
    }
}
