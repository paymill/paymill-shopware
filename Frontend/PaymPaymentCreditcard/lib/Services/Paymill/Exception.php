<?php

/**
 * Services_Paymill_Exception class
 */
class Services_Paymill_Exception extends Exception
{
    /**
     * Constructor for exception object
     *
     * @param string $message
     * @param int    $code
     *
     * @return \Services_Paymill_Exception
     */
  public function __construct($message, $code)
  {
        parent::__construct($message, $code);
  }
}