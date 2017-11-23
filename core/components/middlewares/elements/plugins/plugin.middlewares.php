<?php
if (!function_exists('app')) return;
/** @var modX $modx */
switch ($modx->event->name) {
    case 'OnMODXInit':
        $path = $modx->getOption('middlewares_core_path', null, MODX_CORE_PATH . 'components/middlewares/') . 'classes/';
        require_once $path . 'MiddlewareService.php';
        $mwService =  new Middlewares\MiddlewareService($modx, $path, $this->id);
        app()->instance('MiddlewareService', $mwService);
        app('MiddlewareService')->init();
        break;
    case 'OnLoadWebDocument':
        app('MiddlewareService')->prepareResourceMiddlewares();
        break;
    case 'OnWebPagePrerender':
    case 'OnWebPageComplete':
    case 'OnManagerPageAfterRender':
        app('MiddlewareService')->run($modx->event->name);
        break;
}
/** @var  array $scriptProperties*/
app('MiddlewareService')->handleListeners($modx->event->name, $scriptProperties);
