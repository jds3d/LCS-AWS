<?php
// copyright 2008 Levi Lansing
defined('MUDPUPPY') or die('Restricted');

// static class to assist with accessing request variables (GET,POST,REQUEST,etc)
// handles magic quotes automatically
// modeled after Joomla's Request class (v1.5), but not copied
class Request {

    static private $params;

    /**
     * set the object to represent the 'PARAMS' request dataset. defaults to $_REQUEST
     * @param $params
     */
    static function setParams(&$params) {
        self::$params = &$params;
    }

    static function &getParams() {
        return self::$params;
    }

	/**
	 * called from framework ONE TIME to undo magic quotes damage
	 * @return
	 */
	static function handleMagicQuotes() {
		// handle magic quotes (better if it's off!)
		if (get_magic_quotes_gpc()) {
			foreach ($_POST as &$v) {
				$v = self::stripSlashesRecursive($v);
			}
			foreach ($_GET as &$v) {
				$v = self::stripSlashesRecursive($v);
			}
			foreach ($_COOKIE as &$v) {
				$v = self::stripSlashesRecursive($v);
			}
			foreach ($_REQUEST as &$v) {
				$v = self::stripSlashesRecursive($v);
			}
		}
	}

	/*
	 * get a variable from the given input location
	 */
	static function get($name, $default = null, $inputloc = 'PARAMS') {
		$inputloc = strtoupper($inputloc);

		$input = null;
		switch ($inputloc) {
		case 'GET':
			$input =& $_GET;
			break;
		case 'POST':
			$input =& $_POST;
			break;
		case 'COOKIE':
			$input =& $_COOKIE;
			break;
		case 'FILES':
			$input =& $_FILES;
			break;
		case 'REQUEST':
			$input =& $_REQUEST;
			break;
        case 'PARAMS':
            $input =& self::$params;
            break;
		default:
			if (Config::$debug) {
				throw new Exception("'$inputloc' is not a valid input location");
			}
			return null;
		}

		if (!isset($input[$name])) {
			return $default;
		}

		$var =& $input[$name];

		return $var;
	}

	static function getBool($name, $default = false, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		if (!$var || strncasecmp($var, 'false', 5) == 0 || strncasecmp($var, 'off', 3) == 0 || strncasecmp($var, 'no', 2) == 0) {
			return false;
		}
		return true;
	}

	static function getInt($name, $default = null, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		return self::cleanVar($var, $default, 'int');
	}

	static function getNum($name, $default = null, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		return self::cleanVar($var, $default, 'num');
	}

	static function getDate($name, $default = null, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		return self::cleanVar($var, $default, 'date');
	}

	// get a safe string to be used as a command or filename (not path)
	static function getCmd($name, $default = null, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		return self::cleanVar($var, $default, 'cmd');
	}

	// get a safe path (no ../), can't end with / or .
	static function getPath($name, $default = null, $inputloc = 'PARAMS') {
		$var = self::get($name, $default, $inputloc);
		return self::cleanVar($var, $default, 'path');
	}

	static function &getArray($name, $default = array(), $inputloc = 'PARAMS', $type = null) {
		$var = self::get($name, $default, $inputloc);
		if (!is_array($var)) {
			$var = array($var);
		}
		if (!is_null($type)) {
			$var = self::cleanVar($var, $default, $type);
		}
		return $var;
	}

	// accepts types: int, cmd, path
	static function cleanVar($var, $default, $type) {
		if ($var === $default) {
			return $default;
		}

		if (is_array($var)) {
			//return array_map(array('Request','cleanVar'),$var,array($default,$type));
			foreach ($var as &$v) {
				$v = self::cleanVar($v, $default, $type);
			}
			return $var;
		}

		$pat = '#.*#';
		switch ($type) {
		case 'int':
		case 'num':
			$pat = '#(\-?[0-9]*\.?[0-9]*)#';
			break;
		case 'cmd':
			$pat = '#[a-zA-Z_][a-zA-Z_0-9]*#';
			break;
		case 'path':
			$pat = '#([a-zA-Z_\- 0-9])(/?\.?[a-zA-Z_\- 0-9]+?)+#'; // accepts a . but not a .. to avoid ../
			break;
		case 'date':
			if (empty($var)) {
				return $default;
			}
			$date = strtotime($var);
			if ($date === false) {
				return $default;
			}
			return $date;
		}

		$matches = array();
		if (preg_match($pat, $var, $matches)) {
			$match = $matches[0];
			if (($type == 'num' || $type == 'int') && $match == '') {
				return $default;
			}
			if ($type == 'int') {
				return (int)$match;
			}
			return $matches[0];
		}

		return $default;
	}

	static function getPost($name, $default = null, $type = null) {
		$v = self::get($name, $default, 'POST');
		if ($type != null) {
			$v = self::cleanVar($v, $default, $type);
		}
		return $v;
	}

	static function getGet($name, $default = null, $type = null) {
		$v = self::get($name, $default, 'GET');
		if ($type != null) {
			$v = self::cleanVar($v, $default, $type);
		}
		return $v;
	}

	// check if a variable exists in the given input location
	static function isVar($name, $inputloc = 'PARAMS') {
		$inputloc = strtoupper($inputloc);
		switch ($inputloc) {
		case 'GET':
			return isset($_GET[$name]);
		case 'POST':
			return isset($_POST[$name]);
		case 'FILE':
		case 'FILES':
			return isset($_FILES[$name]);
		case 'COOKIE':
			return isset($_COOKIE[$name]);
        case 'REQUEST':
            return isset($_REQUEST[$name]);
        case 'PARAMS':
            return isset(self::$params[$name]);
		}
        return false;
	}

    static function isPost($name) {
        return self::isVar($name, 'POST');
    }

    static function isParam($name) {
        return self::isVar($name, 'PARAMS');
    }

	static function isGet($name) {
		return self::isVar($name, 'GET');
	}

	static function isFile($name) {
		return self::isVar($name, 'FILE');
	}

	// sets an input variable at the specified location AND _REQUEST
	static function setVar($name, $value, $inputType = 'PARAMS') {
		$inputType = strtoupper($inputType);

		switch ($inputType) {
		case 'GET':
			$_GET[$name] = $value;
			break;
		case 'POST':
			$_POST[$name] = $value;
			break;
		case 'COOKIE':
			$_COOKIE[$name] = $value;
			break;
        case 'REQUEST':
            break;
        case 'PARAMS':
            self::$params[$name] = $value;
            break;
		}
		$_REQUEST[$name] = $value;
	}

	/*
	 * unset a variable
	 */
	static function unsetVar($name, $inputType = 'ALL') {
		$inputType = strtoupper($inputType);

		switch ($inputType) {
		case 'ALL':
			unset($_GET[$name]);
			unset($_POST[$name]);
			unset($_COOKIE[$name]);
            unset(self::$params[$name]);
			break;
		case 'GET':
			unset($_GET[$name]);
			break;
		case 'POST':
			unset($_POST[$name]);
			break;
		case 'COOKIE':
			unset($_COOKIE[$name]);
			break;
        case 'REQUEST':
            break;
        case 'PARAMS':
            unset(self::$params[$name]);
            break;
		}
		unset($_REQUEST[$name]);
	}

	/*
	 * strip slashes (recursively) - specifically for resolving magic quotes
	 * stripSlashesRecursive is called for every array element
	 * stripslashes() is called for each non-array variable
	 */
	private static function stripSlashesRecursive($var) {
		if (is_array($var)) {
			foreach ($var as &$v) {
				$v = self::stripSlashesRecursive($v);
			}
			return $var;
		}
		return stripslashes($var);
	}

}

Request::setParams($_REQUEST);

?>
