<?php
defined('MUDPUPPY') or die('Restricted');

class App {

	private static $dbo = null;

	private function __construct() {
		throw new Exception('App is a static class; cannot instantiate.');
	}

	public static function initialize() {
		// Start the session
		Session::start();

		// Create database object
		self::$dbo = new Database();

		// Connect to database
		$connectSuccess = self::$dbo->connect(Config::$dbHost, Config::$dbDatabase, Config::$dbUser, Config::$dbPass);

		// Display log on failed connection
		if (!$connectSuccess) {
			if (Config::$debug) {
				Log::displayFullLog();
			} else {
				print 'Database Connection Error. Please contact your administrator or try again later.';
				die();
			}
		}

		// Refresh login
		Security::refreshLogin();

		// Application specific startup goes here
	}

	public static function addMessage($message) {
		$currMessages = & Session::getVar('messages', array());
		//Session::setVar('messages', 'THERE WAS AN ERROR!!!');
		$currMessages[] = $message;
	}

	public static function readMessages() {
		$currMessages = Session::getVar('messages', array());
		Session::setVar('messages', array());
		return $currMessages;
	}

	public static function cleanExit($noOutput = false) {
		// record errors to database
        Log::write();

		// Then terminate execution
		if ($noOutput) {
			// Don't let anything print upon exit
			Config::$debug = false;
		}
		exit();
	}

	/**
	 * Get the static database object
	 * @return Database
	 */
	public static function getDBO() {
		return self::$dbo;
	}

	/**
	 * Get the FQ base url of the app
	 */
	public static function getBaseURL() {
		$url = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') {
			$url .= 's';
		}
		$url .= '://' . $_SERVER['HTTP_HOST'];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$url .= ":$_SERVER[SERVER_PORT]";
		}
		$url .= Config::$sitedirectory;
		return $url;
	}

}

?>