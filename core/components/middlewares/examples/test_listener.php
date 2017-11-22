<?php
/**
 * Specify the value "test" in the "middlewares_listeners" system setting.
 */
class TestListener extends Middlewares\Listener
{
    //public $contexts = ['web', 'mgr'];

    public function OnHandleRequest()
    {
        //$this->modx->log(1, '[TestListener] OnHandleRequest event is fired!');
        //$this->modx->regClientScript('<script>alert("OnHandleRequest");</script>');
    }
}

return 'TestListener';