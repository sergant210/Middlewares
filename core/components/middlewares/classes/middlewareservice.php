<?php
namespace Middlewares;

use modX;
use modResource;

class MiddlewareService
{
    protected $modx;
    protected $path;
    protected $globalMiddlewares = array();
    protected $resourceMiddlewares = array();

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        $this->path = $modx->getOption('middlewares_path', null, MODX_CORE_PATH . 'middlewares/');
    }

    public function prepareGlobalMiddlewares()
    {
        $middlewares = $this->modx->getOption('middlewares_global_middlewares', null, '');
        $this->process($middlewares);
    }

    public function prepareResourceMiddlewares()
    {
        $resource = $this->modx->resource;
        if ($resource && $resource instanceof modResource) {
            $middlewares = $resource->getTVValue('middlewares');
            $this->process($middlewares, false);
        }

    }

    protected function addToCollection(Middleware $middleware, $global = true)
    {
        if ($global) {
            $this->globalMiddlewares[] = $middleware;
        } else {
            $this->resourceMiddlewares[] = $middleware;
        }
        return $middleware;
    }

    /**
     * @param string $middlewares
     * @param bool $global
     */
    protected function process($middlewares, $global = true)
    {
        if (!empty($middlewares)) {
            $middlewares = explode_trim(',', $middlewares);
            foreach ($middlewares as $middleware) {
                $file = $this->path . $middleware . '.php';
                if (file_exists($file)) {
                    $class = include_once $file;
                    if (!is_string($class)) {
                        $class = $middleware;
                    }
                    $this->addToCollection(new $class($this->modx), $global)->onRequest();
                }
            }
        }
    }

    /**
     * @param string $event
     */
    public function run($event)
    {
        switch ($event) {
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

}