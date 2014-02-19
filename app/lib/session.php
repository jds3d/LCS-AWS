<?php
// Copyright 2008 Levi Lansing
defined('MUDPUPPY') or die('Restricted');

/*
* class: Session
* contains simplified session and flash functions
* flash data is only stored between pages.	it is only valid for 1 page change.
*/
class Session {
	protected static $sessHash;
	protected static $lastFlashData;
	protected static $_data;

	function __construct() {
		throw new Exception('Session is a static class; cannot instantiate.');
	}

	// to be called by framework before any output
	static function start() {
		session_start();
		if (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['SERVER_NAME'])) {
			self::$sessHash = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']) . '-' . Config::$sitedirectory;
		} else {
			self::$sessHash = 'noserver-' . Config::$sitedirectory;
		}
		self::loadSession();
	}

	private static function loadSession() {
		if (!isset($_SESSION[self::$sessHash])) {
			$_SESSION[self::$sessHash] = array();
		}

		Session::$_data =& $_SESSION[self::$sessHash];

		if (!isset(Session::$_data['flash_data'])) {
			Session::$_data['flash_data'] = array();
		}
		Session::$lastFlashData = Session::$_data['flash_data'];
		Session::$_data['flash_data'] = array();

		// delete expired temp variables
		if (!isset(Session::$_data['_temp'])) {
			Session::$_data['_temp'] = array();
		}

		foreach (Session::$_data['_temp'] as $k => $t) {
			if ($t['exp'] <= time()) {
				unset(Session::$_data['_temp'][$k]);
			}
		}
	}

	/**
	 * clear the entire session variable for this site
	 * @return null
	 */
	static function resetAll() {
		unset($_SESSION[self::$sessHash]);
		$_SESSION[self::$sessHash] = array();

		// reload session
		self::loadSession();
	}

	//////////////////////////////
	// session variable functions

	static function setVar($var, $value) {
		Session::$_data[$var] = $value;
	}

	static function &getVar($var, $default = null) {
		if (isset(Session::$_data[$var])) {
			return Session::$_data[$var];
		}

		Session::$_data[$var] = $default;
		return Session::$_data[$var];
	}

	static function extractVar($var, $default = null) {
		$data = self::getVar($var, $default);
		self::unsetVar($var);
		return $data;
	}

	static function appendVar($var, $value) {
		if (isset(Session::$_data[$var])) {
			Session::$_data[$var][] = $value;
		} else {
			Session::$_data[$var] = array($value);
		}
	}

	static function appendVarString($var, $value) {
		if (isset(Session::$_data[$var])) {
			Session::$_data[$var] .= $value;
		} else {
			Session::$_data[$var] = $value;
		}
	}

	static function isVar($var) {
		return isset(Session::$_data[$var]);
	}

	static function unsetVar($var) {
		unset(Session::$_data[$var]);
	}

	static function clear() {
		foreach (array_keys(Session::$_data) as $key) {
			unset(Session::$_data[$key]);
		}
	}

	// get a temporary variable that will expire in 1800 seconds of last use
	static function &getTempVar($id, $default = null, $ttl = 1800) {
		if (!isset(Session::$_data['_temp'][$id])) {
			Session::$_data['_temp'][$id] = array('exp' => time() + $ttl, 'data' => $default);
		} else {
			Session::$_data['_temp'][$id]['exp'] = time() + $ttl;
		}

		return Session::$_data['_temp'][$id]['data'];
	}

	static function clearTempVar($id) {
		unset(Session::$_data['_temp'][$id]);
	}

	//////////////////////////////
	// Flash functions

	static function setFlash($var, $val) {
		Session::$_data['flash_data'][$var] = $val;
	}

	static function getFlash($var, $default = null) {
		if (isset(Session::$lastFlashData[$var])) {
			return Session::$lastFlashData[$var];
		}

		return $default;
	}

	static function isFlash($var) {
		return isset(Session::$lastFlashData[$var]);
	}

	// frees flash data from previous page
	static function freeFlash() {
		Session::$lastFlashData = array();
	}

	// clears current flash data - will not pass to next page
	static function cancelFlash() {
		Session::$_data['flash_data'] = array();
	}
}

?>