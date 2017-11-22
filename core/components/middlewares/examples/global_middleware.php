<?php

class GlobalMiddleware extends Middlewares\Middleware
{
    //public $contexts = ['mgr','web'];

    public function onRequest()
    {
        // Enable listener.
        // config(['middlewares_listeners' => 'test']);
    }

//    public function beforeResponse(){}

//    public function afterResponse(){}

}
return 'GlobalMiddleware';