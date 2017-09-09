<?php
if ($modx->context->key == 'mgr') return;
/** @var modX $modx */
switch ($modx->event->name) {
    case 'OnMODXInit':
        app()->singleton('MiddlewareService', function() use ($modx) {
           if (!class_exists('MiddlewareService')) {
               $path = $modx->getOption('middlewares_core_path', null, MODX_CORE_PATH . 'components/middlewares/') . 'classes/';
               require_once $path . 'middlewareservice.php';
               require_once $path . 'middleware.php';
           }
           return new MiddlewareService($modx);
        });
        app('MiddlewareService')->prepareGlobalMiddlewares();
        break;
    case 'OnLoadWebDocument':
        app('MiddlewareService')->prepareResourceMiddlewares();
        break;
    case 'OnWebPagePrerender':
    case 'OnWebPageComplete':
        app('MiddlewareService')->run($modx->event->name);
        break;
}