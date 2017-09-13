<?php

namespace Middlewares;

abstract class Listener
{
    protected $modx;

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

}