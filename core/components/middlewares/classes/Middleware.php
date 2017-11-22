<?php
namespace Middlewares;

use modX;

abstract class Middleware
{
    /** @var modX */
    protected $modx;
    /** @var array $contexts Contexts in which the middleware will work. */
    public $contexts = array('web');
    /** @var  bool $global Global flag. Will be set automatically. */
    public $global;

    public function __construct(modX $modx, $global = true)
    {
        $this->modx = $modx;
        $this->global = $global;
    }
    /**
     * Called on the "OnMODXInit" and "OnLoadWebDocument" events.
     */
    public function onRequest(){}
    /**
     * Called on the "OnWebPagePrerender" event.
     */
    public function beforeResponse(){}
    /**
     * Called on the "OnWebPageComplete" event.
     */
    public function afterResponse(){}

}