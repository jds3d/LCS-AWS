<?php
chdir('..');
require('app/framework.php');
require('lib/exceptions.php');

header('Content-type: application/json');
try {
	$requestMethod = $_SERVER['REQUEST_METHOD'];
//	if (count($_REQUEST) > 0 && ($requestMethod == 'POST' || $requestMethod == 'PUT' || $requestMethod == 'DELETE')) {
//		throw new UnsupportedMethodException('Cannot ' . $_SERVER['REQUEST_METHOD'] . ' with query string');
//	}
	$response = null;
	$path = $_SERVER['PATH_INFO'];
	if (preg_match('/^\/([a-zA-Z]+)\/?$/', $path, $matches)) {
        $module = Module::getModuleObject($matches[1]);
		switch ($requestMethod) {
		case 'GET':
			// Retrieve a collection of objects: GET /api/<module>?<params>
            Request::setParams($_GET);
			$response = $module->getCollection($_GET);
			break;
		case 'POST':
			// Create an object: POST /api/<module>
			App::getDBO()->beginTransaction();
            $params = json_decode(file_get_contents('php://input'), true);
            $params = $params != null ? $params : $_POST;
            Request::setParams($params);
			$response = $module->create($params);
			App::getDBO()->commitTransaction();
			break;
		default:
			throw new UnsupportedMethodException('Request method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid for this URL');
		}
	} elseif (preg_match('/^\/([a-zA-Z]+)\/([0-9]+)$\/?/', $path, $matches)) {
//		if (count($_REQUEST) > 0) {
//			throw new UnsupportedMethodException('Query strings are not allowed for this URL');
//		}
        $module = Module::getModuleObject($matches[1]);
		switch ($requestMethod) {
		case 'GET':
			// Retrieve a single object: GET /api/<module>/id
            $params = array('id'=>(int)$matches[2]);
            Request::setParams($params);
			$response = $module->get((int)$matches[2]);
			break;
		case 'PUT':
			// Update an object: PUT /api/<module>/id
			App::getDBO()->beginTransaction();
            $params = json_decode(file_get_contents('php://input'), true);
            $params = $params != null ? $params : $_POST;
            Request::setParams($params);
			$response = $module->update((int)$matches[2], $params);
			App::getDBO()->commitTransaction();
			break;
		case 'DELETE':
			// Delete an object: DELETE /api/<module>/id
			App::getDBO()->beginTransaction();
            $params = array('id' => (int)$matches[2]);
            Request::setParams($params);
			$response = $module->delete((int)$matches[2]);
			App::getDBO()->commitTransaction();
			break;
		default:
			throw new UnsupportedMethodException('Request method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid for this URL');
		}
	} elseif (preg_match('/^\/([a-zA-Z]+)\/([a-zA-Z]+)\/?$/', $path, $matches)) {
        $module = Module::getModuleObject($matches[1]);
		switch ($requestMethod) {
		case 'GET':
			//App::getDBO()->beginTransaction();
			// Call an action: GET /api/<module>/<action>?<params>
            Request::setParams($_GET);
			$response = $module->runAction($matches[2], $_GET);
			//App::getDBO()->commitTransaction();
			break;
		case 'POST':
			// Call an action: POST /api/<module>/<action>
			App::getDBO()->beginTransaction();						
            $params = json_decode(file_get_contents('php://input'), true);
            $params = $params != null ? $params : $_POST;
            Request::setParams($params);
			$response = $module->runAction($matches[2], $params);
			App::getDBO()->commitTransaction();
			break;
		default:
			throw new UnsupportedMethodException('Request method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid for this URL');
		}
	} else {
		throw new InvalidIdentifierException('Invalid URL schema');
	}

    if ($response != null)
	    print json_encode($response);

} catch (ApiException $e) {
	App::getDBO()->rollBackTransaction();
	http_response_code($e->getCode());
	print json_encode(array('message' => $e->getMessage()));
} catch (Exception $e) {
	// TODO: do log the error
	App::getDBO()->rollBackTransaction();
	http_response_code(500);
	print json_encode(array('message' => 'The server encountered an unexpected error. See log for details.'));
}

App::cleanExit(true);

?>