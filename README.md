## Middlewares

It's a middlewares emulator for MODx Revolution based on classes. But not only. In addition you can use class-based listeners instead of usual plugins.

#### Middlewares
Middlewares can be used to handle the request, and also allow you to perform certain actions after the response is prepared.
![https://modzone.ru/assets/images/documentation/middlewares_thumb.jpg](https://modzone.ru/assets/images/documentation/middlewares.jpg)

Middlewares are based on classes. They can be global (fires on every request) or custom (fires only for specified resources). The global middlewares are specified in the "middlewares_global_middlewares" system setting. The custom ones must be set in a resource TV with name "middlewares".
The middleware class have 3 methods:
- onRequest - called on the OnMODXInit event.
- beforeResponse - called on the OnWebPagePrerender event.
- afterResponse - called on the OnWebPageComplete event.

##### Usage
Let's create a global middleware. Create a file in the folder *core/middlewares/*. Let's call it *global.php*. 
```$php
<?php 
// global.php

class GlobalMiddleware extends Middlewares\Middleware
{
    public function onRequest() 
    {
        $this->modx->log(1, 'Save this message in the error log.');
    }

    public function beforeResponse() 
    {
        // Change the output
        $this->modx->resource->_output = 'New content for output';
    }

    public function afterResponse() {}
}
// If the class name and file name do not match, then you need to return the class name.
return 'GlobalMiddleware';
```
In the next step we need to specify the file name without extension ("global") in the "middlewares_global_middlewares" system setting. That's all.
 
The "contexts" property is intended to specify contexts in which the middleware will be work.
```$php
// By default the web context is set.
public $contexts = array('web');
```
Specify an empty array to ignore context checking.

##### Run a middleware manually
```php
$class = app('MiddlewareService')->loadMiddlewareClass('CsrfMiddleware');
(new $class($this->modx))->onRequest();
```

#### Listeners
Listeners (event handlers) like middlewares are classes that are located in the folder specified in the "listeners_path" system settings. By default "core/listeners".

##### How to use it
Create a file in the corresponding folder. Now you need to create a public method and give it the name of the corresponding MODX event.
```$php
<?php
// ListenerManager.php

class ListenerManager extends Middlewares\Listener
{
    public $contexts = ['web', 'mgr'];
	
    public function OnHandleRequest() 
    {
        this->modx->log(1, '[ListenerManager] OnHandleRequest event is fired!';
        $this->modx->regClientScript('<script>alert("OnHandleRequest");</script>');
    }
    
	public function OnBeforeManagerPageInit()
    {
        $this->modx->controller->addHtml('<script>alert("OnBeforeManagerPageInit");</script>');
    }
    
    public function OnBeforeDocFormSave($properties) 
    {
        extract($properties);
        if (empty($resource->longtitle)) {
            $this->modx->event->output('['Long title is required!'); // to modal window
            $this->modx->log(1, '[ListenerManager] Failed to save page id '.$id.' due to missing longtitle'; // to the error log
        }
    }
}
// If the class name and file name do not match, then you need to return the class name.
// In this case they are the same. So no need to return it.
// return 'ListenerManager';
```
Put the file name in the "middlewares_listeners" system setting. Or you can do it in the global middleware:
```$php
// global.php

class GlobalMiddleware extends Middlewares\Middleware
{
    public function onRequest() 
    {
        $this->modx->setOption('middlewares_listeners', 'ListenerManager');
    }
}

return 'GlobalMiddleware';
```
if you want to run your listeners before any MODX plugins, specify the argument "before" of the corresponding method:
```$php
public function OnHandleRequest($before=true) 
{
    $this->modx->log(1, '[ListenerManager] OnHandleRequest event is fired!');
    $this->modx->regClientScript('<script>alert("OnHandleRequest");</script>');
}
    
public function OnBeforeDocFormSave($properties, $before=true) 
{
    extract($properties);
    if (empty($resource->longtitle)) {
        $this->modx->event->output('Long title is required!'); // to modal window
        $this->modx->log(1, '[ListenerManager] Failed to save page id '.$id.' due to missing longtitle'!); // to the error log
    }
}
```
In most cases you can refuse to use usual MODX plugins.

#### Example files 
You can find a middleware file in the *core/middlewares* directory and a listener file in the *core/listeners* directory. The middleware is ready for use right after installation.

#### System settings
- middlewares_path - path to middlewares' classes. By-default "{core_path}middlewares/".
- middlewares_lpath	- path to listeners' classes. By-default, "{core_path}listeners/".
- middlewares_global_middlewares - comma separated names of middlewares.
- middlewares_listeners	- comma separated names of listeners.