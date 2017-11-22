<?php

namespace Middlewares;

use modX;

abstract class Listener
{
    /** @var modX */
    protected $modx;
    /** @var array $contexts Contexts in which the listener will work. */
    public $contexts = array('web');

    /**
     * Listener constructor.
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

}