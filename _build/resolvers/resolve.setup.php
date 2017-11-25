<?php
/**
 * Resolves setup-options settings
 *
 * @var xPDOObject $object
 * @var array $options
 */

$success = false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
        // Make an example of the middleware
        $middleware = array(
            'source' => MODX_CORE_PATH . 'components/middlewares/examples/init_middleware.php',
            'file' => MODX_CORE_PATH . 'middlewares/init.php',
        );
        if (!file_exists(MODX_CORE_PATH . 'middlewares')) {
            mkdir(MODX_CORE_PATH . 'middlewares', 0755);
        }
        if (!file_exists($middleware['file'])) {
            copy($middleware['source'], $middleware['file']);
        }
        // Make an example of the listener
        $listener = array(
            'source' => MODX_CORE_PATH . 'components/middlewares/examples/test_listener.php',
            'file' => MODX_CORE_PATH . 'listeners/test.php',
        );
        if (!file_exists(MODX_CORE_PATH . 'listeners')) {
            mkdir(MODX_CORE_PATH . 'listeners', 0755);
        }
        if (!file_exists($listener['file'])) {
            copy($listener['source'], $listener['file']);
        }
        $success = true;
        break;

    case xPDOTransport::ACTION_UPGRADE:
        $success = true;
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $success = true;
        break;
}


return $success;