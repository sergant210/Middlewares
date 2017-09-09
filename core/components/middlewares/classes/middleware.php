<?php

abstract class Middleware
{
    public $contexts = array();

    protected $modx;

    public function __construct($modx)
    {
        $this->modx = $modx;
    }

    abstract public function onRequest();

    abstract public function beforeResponse();

    abstract public function afterResponse();

}