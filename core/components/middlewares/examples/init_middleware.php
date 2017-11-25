<?php

/**
 * Class InitMiddleware
 * This middleware can be used to define settings, switch the context, manage other middlewares and listeners.
 */
class InitMiddleware extends Middlewares\Middleware
{
    //public $contexts = ['web','mgr'];

    public function onRequest()
    {
        // Enable listener.
        // config(['middlewares_listeners' => 'test']);
    }

//    public function beforeResponse(){}

//    public function afterResponse(){}

}
return 'InitMiddleware';