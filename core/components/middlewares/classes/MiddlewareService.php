<?php
namespace Middlewares;

use modX;
use modResource;
use ReflectionObject;
use ReflectionMethod;

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

        require_once $path . 'Middleware.php';
        require_once $path . 'Listener.php';
    }

    /**
     * Prepare global middlewares and listeners.
     */
    public function init()
    {
        $this->prepareGlobalMiddlewares();
        $this->prepareListener();
    }

    /**
     * Get names of the middlewares from the system setting and load their classes.
     */
    public function prepareGlobalMiddlewares()
    {
        $middlewares = $this->modx->getOption('middlewares_global_middlewares', null, '');
        if (!empty($middlewares)) $this->process(explode_trim(',', $middlewares), true);
    }
    /**
     * Get names of the listeners from the system setting and load their classes.
     */
    public function prepareListener()
    {
        $listeners = $this->modx->getOption('middlewares_listeners', null, '');
        if (!empty($listeners)) $this->process(explode_trim(',', $listeners), false);
    }

    /**
     * Get names of the middlewares from the resource TV and load their classes.
     */
    public function prepareResourceMiddlewares()
    {
        $resource = $this->modx->resource;
        if ($resource && $resource instanceof modResource) {
            $middlewares = $resource->getTVValue('middlewares');
            if (!empty($middlewares)) $this->process(explode_trim(',', $middlewares), true);
        }
    }

    /** Add a middleware to the collection.
     * @param Middleware $middleware
     * @return Middleware|null
     */
    public function addMiddleware(Middleware $middleware)
    {
        if (!$this->checkContext($middleware)) return null;
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
        foreach ($elements as $element) {
            if ($class = $this->loadClass($element, $isMiddleware)) {
                if ($isMiddleware) {
                    $this->addMiddleware(new $class($this->modx, $this->modx->event->name == 'OnMODXInit'));
                } else {
                    $this->addEventListener(new $class($this->modx));
                }
            }
        }
        if ($isMiddleware) {
            $this->run($this->modx->event->name);
        }
    }

    /**
     * Execute the middlewares.
     * @param string $event
     */
    public function run($event)
    {
        $method = 'onRequest';
        switch ($event) {
            case 'OnMODXInit':
                $middlewares = $this->globalMiddlewares;
                break;
            case 'OnLoadWebDocument':
                $middlewares = $this->resourceMiddlewares;
                break;
        	case 'OnWebPagePrerender':
                $method = 'beforeResponse';
                $middlewares = array_merge($this->resourceMiddlewares, $this->globalMiddlewares);
        		break;
            case 'OnWebPageComplete':
                $method = 'afterResponse';
                $middlewares = array_merge($this->resourceMiddlewares, $this->globalMiddlewares);
                break;
            default:
                return;
        }
        foreach ($middlewares as $middleware) {
            $middleware->{$method}();
        }
    }

    /**
     * @param string $name File name without extension.
     * @param bool $isMiddleware TRUE if it's a middleware, FALSE for a listener.
     * @return mixed
     */
    protected function loadClass($name, $isMiddleware)
    {
        $path = $isMiddleware ? $this->path : $this->lpath;
        $file = $path . $name . '.php';
        if (file_exists($file)) {
            $class = include_once $file;
            if (!is_string($class)) {
                $class = $name;
            }
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Middlewares] File ' . $file . ' does not exist!');
            return null;
        }

        return $class;
    }

    /**
     * Load a middleware class.
     * @param string $name File name without extension.
     * @return string|null
     */
    public function loadMiddlewareClass($name)
    {
        return $this->loadClass($name, true);
    }

    /**
     * Load a listener class.
     * @param string $name File name without extension.
     * @return string|null
     */
    public function loadListenerClass($name)
    {
        return $this->loadClass($name, false);
    }

    /**
     * @param string $event Event name
     * @param array $properties
     * @return void
     */
    public function handleListeners($event, $properties = null)
    {
        if (!empty($this->eventMap[$event])) {
            foreach ($this->eventMap[$event] as $listener) {
                $listener->{$event}($properties);
            }
        }
    }

    /**
     * @param Listener $listener
     */
    public function addEventListener(Listener $listener)
    {
        if (!$this->checkContext($listener)) return;
        $class = get_class($listener);
        $classReflection = new ReflectionObject($listener);
        $methods = $classReflection->getMethods();
        foreach ($methods as $method) {
            if ($method->isConstructor()) continue;
            $before = false;
            $methodReflection = new ReflectionMethod($class, $method->name);
            foreach ($methodReflection->getParameters() as $param) {
                if ($param->name == 'before') {
                    $before = (bool) $param->getDefaultValue();
                }
            }
            $this->eventMap[$method->name][] = $listener;
            if ($before) {
                $this->modx->eventMap[$method->name] = array($this->pluginId => $this->pluginId) + ($this->modx->eventMap[$method->name] ?: array());
            } else {
                $this->modx->eventMap[$method->name][$this->pluginId] = $this->pluginId;
            }
//            $this->modx->addEventListener($method->name, $this->pluginId);
        }
    }

    /**
     * Check the context of the element.
     * @param Middleware|Listener $element Middleware or Listener.
     * @return bool
     */
    protected function checkContext($element)
    {
        return empty($element->contexts) || in_array(context(), $element->contexts);
    }
}