<?php
/**
 * ajax repsonse handler for all responses to ajax requests
 * response handled in responsehandler.js
 * although many actions are defined here, the intent was for the actions to be defined by
 * the module that handles the request, thus some actions will be defined in the modules
 * 
 */
class AjaxResponse{

	public static $ACTION_SET_CONTENT = "setContent";
	public static $ACTION_LOG = "log";
	public static $ACTION_DISP_DEBUG = "dispdebug";
	public static $ACTION_DEBUG = "debug";
	public static $ACTION_ERROR = "error";			// display an error to the user
	public static $ACTION_DISP_MESSAGE = "displayMessage";

	var $response;

	function __construct(){

		$this->response = Array();
	}

	/**
	 * set a data field for the current action
	 * 
	 * @param string $field
	 * @param string $value
	 */
	function setData( $field, $value ){

		$this->response[count($this->response)-1]['data'][$field] = $value;
	}

	/**
	 * add an action to the ajax response
	 * subsequent calls to setData will set data for this action
	 * 
	 * @param string $action
	 * @param array $data
	 * @param mixed $instanceId
	 */
	function addAction( $action, $data = array(), $instanceId=-1){
		if ($instanceId === -1) {
			$instanceId = Request::get('instanceId', -1);
		}
		$response = array( 'action'=>$action, 'data'=>$data);
		if ($instanceId !== -1 && !is_null($instanceId)) {
			$response['instanceId'] = $instanceId;
		}
		$this->response[] = $response;
	}

	/**
	 * convience method to display an error to the user
	 * @param string $message
	 */
	function displayError($message) {
		$this->addAction(self::$ACTION_DISP_MESSAGE);
		$this->setData('type', 'error');
		$this->setData('message', $message);
	}

	/**
	 * convience method to display a warning to the user
	 * @param string $message
	 */
	function displayWarning($message) {
		$this->addAction(self::$ACTION_DISP_MESSAGE);
		$this->setData('type', 'warning');
		$this->setData('message', $message);
	}

	/**
	 * convience method to display a message to the user
	 * @param string $message
	 */
	function displayInfo($message) {
		$this->addAction(self::$ACTION_DISP_MESSAGE);
		$this->setData('type', 'info');
		$this->setData('message', $message);
	}


	/**
	 * send the completed response and exit
	 */
	function send(){
		if (!headers_sent())
			header('Content-type: application/json');
		if (Config::$debug) {
			$this->addAction(self::$ACTION_LOG, array('html'=>Log::getFullLog()) );
		}
		if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
			header('Content-Encoding: gzip');
			print gzencode(json_encode($this->response));
		} else {
			print json_encode($this->response);
		}
		App::cleanExit(true);
	}

	/**
	 * send a standard command to tell javascript to load
	 * this content into the jQuery selector for this instance
	 *
	 * @param string $selector the jQuery selector for the DOM element to update
	 * @param string $content the HTML content to display
	 * @param mixed $instanceId the instance to send the response to
	 */
	function displayContent($selector, $content, $instanceId=-1) {
		if ($instanceId === -1) {
			$instanceId = Request::get('instanceId', -1);
		}
		$data = array('content'=>$content, 'selector'=>$selector, 'instanceId'=>$instanceId);
		$this->addAction(AjaxResponse::$ACTION_SET_CONTENT, $data);
	}

	/**
	 * create a list of arrays containing only the values of the data objects
	 * 
	 * @param array $array of objects inheriting from DataObject
	 */
	static function convertArrayOfDataObjectsForJSON($array) {
		$newList = array();
		/**
		 * DataObject $obj
		 */
		foreach ($array as $obj) {
			$newList[] = $obj->toArray();
		}
		return $newList;
	}
	
	
	static function SendResponse($action, $data=array(), $instanceId=-1) {
		$response = new AjaxResponse();
		$response->addAction($action, $data, $instanceId);
		$response->send();
	}
		
	static function SendError($message, $instanceId=-1) {
		$response = new AjaxResponse();
		$response->addAction(self::$ACTION_DISP_MESSAGE, array('type'=>'error', 'message'=>$message), $instanceId);
		$response->send();
	}
}

?>