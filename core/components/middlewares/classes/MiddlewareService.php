<?php
namespace Middlewares;

use modX;
use modResource;
use ReflectionException;
use ReflectionMethod;

class MiddlewareService
{
    protected $modx;
    protected $mpath;
    protected $lpath;
    protected $globalMiddlewares = array();
    protected $resourceMiddlewares = array();
    protected $eventMap = array();
    protected $bootedClasses = array(
        'middlewares' => array(),
        'listeners' => array(),
    );
    public $pluginId;

    public function __construct(modX $modx, $path, $pluginId)
    {
        $this->modx = $modx;
        $this->mpath = $modx->getOption('middlewares_path', null, MODX_CORE_PATH . 'middlewares/');
        $this->lpath = $modx->getOption('middlewares_lpath', null, MODX_CORE_PATH . 'listeners/');
        $this->pluginId = $pluginId;

        require_once $path . 'Middleware.php';
        require_once $path . 'Listener.php';
    }

    /**
     * Prepare global middlewares and listeners.
     * @throws ReflectionException
     */
    public function init()
    {
        $this->prepareGlobalMiddlewares();
        $this->prepareListeners();
    }

    /**
     * Get names of the middlewares from the system setting and load their classes.
     * @throws ReflectionException
     */
    public function prepareGlobalMiddlewares()
    {
        $middlewares = $this->modx->getOption('middlewares_global_middlewares', null, '');
        if (!empty($middlewares)) {
            $this->process(explode_trim(',', $middlewares), true);
        }
    }

    /**
     * Get names of the listeners from the system setting and load their classes.
     * @throws ReflectionException
     */
    public function prepareListeners()
    {
        $listeners = $this->modx->getOption('middlewares_listeners', null, '');
        if (!empty($listeners)) {
            $this->process(explode_trim(',', $listeners), false);
        }
    }

    /**
     * Get names of the middlewares from the resource TV and load their classes.
     * @param string|array $middlewares
     * @throws ReflectionException
     */
    public function prepareResourceMiddlewares($middlewares = '')
    {
        if ($middlewares) {
            if (!is_array($middlewares)) {
                $middlewares = explode_trim(',', $middlewares);
            }
            $this->process($middlewares, true);
        } else {
            $resource = $this->modx->resource;
            if ($resource && $resource instanceof modResource) {
                $middlewares = $resource->getTVValue('middlewares');
                if (!empty($middlewares)) {
                    $this->process(explode_trim(',', $middlewares), true);
                }
            }
        }
    }

    /** Add a middleware to the collection.
     * @param Middleware $middleware
     * @return Middleware|null
     */
    public function addMiddleware(Middleware $middleware)
    {
        if (!$this->checkContext($middleware)) {
            return null;
        }
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
     * @throws \ReflectionException
     */
    protected function process($elements, $isMiddleware)
    {
        foreach ($elements as $element) {
            if (($class = $this->loadClass($element, $isMiddleware)) && class_exists($class)) {
                try {
                    if ($isMiddleware) {
                        $this->addMiddleware(new $class($this->modx, $this->modx->event->name == 'OnMODXInit'));
                    } else {
                        $this->addEventListener(new $class($this->modx));
                    }
                } catch (\InvalidArgumentException $e) {
                    log_error('[Middlewares] ' . $e->getMessage());
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
        	case 'OnManagerPageAfterRender':
                $method = 'beforeResponse';
                $middlewares = $this->globalMiddlewares;
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
        $type = $isMiddleware ? 'middlewares' : 'listeners';
        if (isset($this->bootedClasses[$type][$name])) {
            return $this->bootedClasses[$type][$name];
        }
        $path = $isMiddleware ? $this->mpath : $this->lpath;
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
        if (!class_exists($class)) {
            return null;
        }
        $this->bootedClasses[$type][$name] = $class;
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
     * @throws \ReflectionException
     */
    public function addEventListener(Listener $listener)
    {
        if (!$this->checkContext($listener)) {
            return;
        }
        foreach (get_class_methods($listener) as $method) {
            if ($method === '__construct') {
                continue;
            }
            $before = false;
            $methodReflection = new ReflectionMethod(get_class($listener), $method);
            foreach ($methodReflection->getParameters() as $param) {
                if ($param->name === 'before') {
                    $before = (bool) $param->getDefaultValue();
                    break;
                }
            }
            $this->eventMap[$method][] = $listener;
            if ($before) {
                $this->modx->eventMap[$method] = array($this->pluginId => $this->pluginId) + ($this->modx->eventMap[$method] ?: array());
            } else {
                $this->modx->eventMap[$method][$this->pluginId] = $this->pluginId;
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