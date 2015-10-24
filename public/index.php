<?php

/**
 * Driver for PHP HMAC Restful API using PhalconPHP's Micro framework
 * 
 * @package None
 * @author  Jete O'Keeffe 
 * @license none
 */


// Setup configuration files
$dir = dirname(__DIR__);
$appDir = $dir . '/app';

// Necessary requires to get things going
require $appDir . '/library/Utilities/Debug/PhpError.php';
require $appDir . '/library/Interfaces/IRun.php';
require $appDir . '/library/Application/Micro.php';

// Capture runtime errors
register_shutdown_function(['Utilities\Debug\PhpError','runtimeShutdown']);

// Necessary paths to autoload & config settings
$configPath = $appDir . '/config/';
$config = $configPath . 'config.php';
$autoLoad = $configPath . 'autoload.php';
$routes = $configPath . 'routes.php';

use \Models\Api as Api;

try {
	$app = new Application\Micro();

	// Record any php warnings/errors
	set_error_handler(['Utilities\Debug\PhpError','errorHandler']);

	// Setup App (dependency injector, configuration variables and autoloading resources/classes)
	$app->setAutoload($autoLoad, $appDir);
	$app->setConfig($config);

	// Get Authentication Headers - Apache compatible (need to replace _ by - on client side)
	$clientId = $app->request->getHeader('X_API_ID');
	$time = $app->request->getHeader('X_API_TIME');
	$hash = $app->request->getHeader('X_API_HASH');

    $api = Api::findFirst("public_id = '$clientId'");
	$privateKey = $api ? $api->private_key : null;
	
        switch ($_SERVER['REQUEST_METHOD']) {
            
            case 'GET':
                $data = $_GET;
                unset($data['_url']); // clean for hashes comparison
                break;
            
            case 'POST':
                $data = $_POST;
                break;

            default: // PUT AND DELETE
                $data = file_get_contents('php://input');
                break;
        }
	$message = new \Micro\Messages\Auth($clientId, $time, $hash, $data);

	// Setup HMAC Authentication callback to validate user before routing message
	// Failure to validate will stop the process before going to proper Restful Route
	$app->setEventsManager(new \Events\Api\HmacAuthenticate($message, $privateKey));

	// Setup RESTful Routes
	$app->setRoutes($routes);

	// Boom, Run
	$app->run();

} catch(Exception $e) {
	// Do Something I guess, return Server Error message
	$app->response->setStatusCode(500, "Server Error");
	$app->response->setContent($e->getMessage());
	$app->response->send();
}
