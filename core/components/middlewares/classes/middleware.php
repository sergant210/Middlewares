<?php
namespace Middlewares;

use modX;

abstract class Middleware
{
    public $contexts = array();

    protected $modx;

    public $global;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    abstract public function onRequest();

    abstract public function beforeResponse();

    abstract public function afterResponse();

}