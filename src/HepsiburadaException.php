<?php


namespace Hepsiburada;


use Throwable;

class HepsiburadaException extends \Exception
{
    public function __construct(\Exception $e)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }
}