<?php
namespace Middlewares;

use modX;
use modResource;
use ReflectionObject;

class MiddlewareService
{
    protected $modx;
    protected $path;
    protected $lpath;
    protected $globalMiddlewares = array();
    protected $resourceMiddlewares = array();
    protected $eventMap = array();
    public $pluginId;

    public function __construct(modX $modx, $path, $pluginId)
    {
        $this->modx = $modx;
        $this->path = $modx->getOption('middlewares_path', null, MODX_CORE_PATH . 'middlewares/');
        $this->lpath = $modx->getOption('middlewares_lpath', null, MODX_CORE_PATH . 'listeners/');
        $this->pluginId = $pluginId;

        require_once $path . 'middleware.php';
        require_once $path . 'listener.php';
    }

    public function init()
    {
        $this->prepareGlobalMiddlewares();
        $this->prepareListener();
    }

    public function prepareListener()
    {
        $listeners = $this->modx->getOption('middlewares_listeners', null, '');
        if (!empty($listeners)) $this->process(explode_trim(',', $listeners), false);
    }

    public function prepareGlobalMiddlewares()
    {
        $middlewares = $this->modx->getOption('middlewares_global_middlewares', null, '');
        if (!empty($middlewares)) $this->process(explode_trim(',', $middlewares), true);
    }

    public function prepareResourceMiddlewares()
    {
        $resource = $this->modx->resource;
        if ($resource && $resource instanceof modResource) {
            $middlewares = $resource->getTVValue('middlewares');
            if (!empty($middlewares)) $this->process(explode_trim(',', $middlewares), true);
        }
    }

    protected function addMiddlewares(Middleware $middleware)
    {
        if ($middleware->global) {
            $this->globalMiddlewares[] = $middleware;
        } else {
            $this->resourceMiddlewares[] = $middleware;
        }
        return $middleware;
    }

    /**
     * @param array $elements
     * @param bool $isMiddleware
     */
    protected function process($elements, $isMiddleware)
    {
        $path = $isMiddleware ? $this->path : $this->lpath;
        foreach ($elements as $element) {
            if ($class = $this->loadClass($path, $element)) {
                if ($isMiddleware) {
                    $this->run($this->modx->event->name, new $class($this->modx, $this->modx->event->name == 'OnMODXInit'));
                } else {
                    $this->createEventMap(new $class($this->modx));
                }
            }
        }
    }

    /**
     * @param string $event
     * @param Middleware|null $middleware
     */
    public function run($event, Middleware $middleware = null)
    {
        switch ($event) {
            case 'OnMODXInit':
            case 'OnLoadWebDocument':
                if (!is_null($middleware)) $this->addMiddlewares($middleware)->onRequest();
                return;
                break;
        	case 'OnWebPagePrerender':
                $method = 'beforeResponse';
        		break;
            case 'OnWebPageComplete':
                $method = 'afterResponse';
                break;
            default:
                $method = '';
        }
        if ($method) {
            foreach (array_merge($this->resourceMiddlewares, $this->globalMiddlewares) as $middleware) {
                $middleware->$method();
            }
        }
    }

    /**
     * @param $path
     * @param $className
     * @return mixed
     */
    protected function loadClass($path, $className)
    {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            $class = include_once $file;
            if (!is_string($class)) {
                $class = $className;
            }

        }
        return isset($class) ? $class : null;
    }

    public function handleListeners($event, $properties = null)
    {
        if (empty($this->eventMap[$event])) return;
        foreach ($this->eventMap[$event] as $listener) {
            $listener->$event($properties);
        }
    }

    /**
     * @param Listener $listener
     */
    protected function createEventMap(Listener $listener)
    {
        $class = new ReflectionObject($listener);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            if ($method->isConstructor()) continue;
            $this->eventMap[$method->name][] = $listener;
            $this->modx->addEventListener($method->name, $this->pluginId);
        }
    }
}