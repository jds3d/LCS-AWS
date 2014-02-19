<?php
define('MUDPUPPY', 'running');

error_reporting(E_ALL);

require_once('lib/log.php'); // required for exceptions
require_once("lib/opmCalculator.php");

if (!include_once( 'app/config.php')) {
	die('Cannot start application! Config file is missing.');
}

if (!Config::$debug) {
	error_reporting(0); // disable error handling when not in debug
} else {
	ini_set('display_errors', '1');
}

date_default_timezone_set(Config::$timezone);

// ver < PHP 5.2
if (!defined('E_RECOVERABLE_ERROR')) {
	define('E_RECOVERABLE_ERROR', 4096);
}

// ver < PHP 5.4
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {
        static $lastCode = 200;
        if (empty($code))
           return $lastCode;
        $lastCode = $code;

        if ($code !== NULL) {
            switch ($code) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default:
                    exit('Unknown http status code "' . htmlentities($code) . '"');
                    break;
            }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $code . ' ' . $text);
        }

        return $code;

    }
}

// Activate assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

// Set up error/assertion handlers (functions at bottom of file)
assert_options(ASSERT_CALLBACK, '_assert_handler');
set_exception_handler('exception_handler');
set_error_handler('error_handler');
register_shutdown_function('shutdown_handler');

require_once('lib/file.php'); // required for autoload

/////////////////////////////////////
// autoload classes on demand
/////////////////////////////////////
function MPAutoLoad($className) {
	static $classes = null;
	$classcachefile = 'app/cache/classlocationcache.php';

	$parts = explode('\\', $className);
	$class = strtolower($parts[sizeof($parts) - 1]);
	array_pop($parts);
	$namespace = strtolower(implode('/', $parts));

	if (is_null($classes)) {
		// load class location cache
		if (file_exists($classcachefile)) {
			include($classcachefile);
		}
	}

	if (($namespace && (!isset($classes[$namespace]) || !isset($classes[$namespace][$class]) || !file_exists($classes[$namespace][$class])))
		|| (!$namespace && (!isset($classes[$class]) || !file_exists($classes[$class])))
	) {

		// first check if it is part of a lib with its own autoloader
		if (strncasecmp($class, 'PHPExcel_', 9) == 0) {
			return false;
		}

		// can't find the class, refresh class list
		$code = _refreshAutoLoadClasses($classes);
		File::putContents($classcachefile, $code);
	}

	$file = null;
	if ($namespace && isset($classes[$namespace]) && isset($classes[$namespace][$class]) && file_exists($classes[$namespace][$class])) {
		$file = $classes[$namespace][$class];
	} else if (isset($classes[$class]) && file_exists($classes[$class])) {
		$file = $classes[$class];
	}

	if ($file) {
		require_once($file);
		return true;
	}

	// we failed, this class is not locateable
	//exception_handler(new Exception("failed to locate class: $className"));
	return false;
}

spl_autoload_register('MPAutoLoad');

function _refreshAutoLoadClasses(&$classes) {
	Log::add("Refreshing auto-load cache");
	$classes = array();

	// note, modules do not need to be cached as they are loaded on demand without __autoload()
	// also, if a model has the same class name as a class in the library or system,
	// the library or system class will take precedence

	$folders = array('app/dataobjects/', 'app/modules/', 'app/system/', 'app/lib/');
	foreach ($folders as $folder) {
		$files = File::getFileList($folder, false, true, false, '#.*\.php$#');
		_ralc_parsefiles($classes, $files, $folder);
	}

	// assume all folders inside dataobjects are a namespace
	$folder = 'app/dataobjects/';
	$namespaces = File::getFileList('app/dataobjects/', false, false, true);
	foreach ($namespaces as $namespace) {
		$nsClasses = array();
		$files = File::getFileList($folder . $namespace, false, true, false, '#.*\.php$#');
		_ralc_parsefiles($nsClasses, $files, $folder . $namespace . '/');
		$classes[$namespace] = $nsClasses;
	}

	$code = "<?php \$classes = " . var_export($classes, true) . "; ?>";
	return $code;
}

function _ralc_parsefiles(&$classes, &$files, $folder) {
	foreach ($files as $file) {
		$class = strtolower(File::getTitle($file, false));
		if ($class) {
			$classes[$class] = $folder . $file;
		}
	}
}

// OMG i thought i didn't need this anymore until my default MAMP install set magic quotes!
Request::handleMagicQuotes();

// need to pre-load ErrorLog data object in order to write to DB during shutdown
MPAutoLoad('ErrorLog');
MPAutoLoad('DateHelper');

App::initialize();

// some error handling

// assertion handling
function _assert_handler($file, $line, $code) {
	if (Config::$debug) {
		throw(new Exception("Assertion failed in file $file($line).\nCode: $code"));
	}
}

// error handler function
function error_handler($errno, $errstr, $errfile, $errline) {
	// timestamp for the error entry
	$dt = date("Y-m-d H:i:s (T)");

	// define an assoc array of error string
	// in reality the only entries we should
	// consider are E_WARNING, E_NOTICE, E_USER_ERROR,
	// E_USER_WARNING and E_USER_NOTICE
	$errortype = array(
		E_ERROR => 'Error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parsing Error',
		E_NOTICE => 'Notice',
		E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning',
		E_COMPILE_ERROR => 'Compile Error',
		E_COMPILE_WARNING => 'Compile Warning',
		E_USER_ERROR => 'User Error',
		E_USER_WARNING => 'User Warning',
		E_USER_NOTICE => 'User Notice',
		E_STRICT => 'Runtime Notice',
		E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
	);
	// set of errors for which a var trace will be saved
	$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

	$err = $errortype[$errno] . ": $errstr in $errfile ($errline)";
	switch ($errno) {
	case E_ERROR:
	case E_PARSE:
	case E_CORE_ERROR:
	case E_COMPILE_ERROR:
	case E_USER_ERROR:
		exception_handler(new Exception($err));
		break;
	default:
		//exception_handler(new Exception($err));
		Log::error($err);
	}

	// Don't execute PHP internal error handler
	return true;
}

// exception handler
function exception_handler(Exception $exception) {
	$error_data = array('type' => 'Exception', 'errno' => $exception->getCode(), 'message' => $exception->getMessage(),
		'file' => $exception->getFile(), 'line' => $exception->getLine(), 'trace' => $exception->getTraceAsString());
	Log::error('An exception has occurred in ' . $exception->getFile() . '(' . $exception->getLine() . "). \nMessage: " . $exception->getMessage());

	if (Config::$debug) {
		Log::write();
	} else {
		/* redirect to error page */
		print('A Fatal Error Occurred.');
	}

	App::cleanExit();
}

function shutdown_handler() {
	$error = error_get_last();
	if ($error !== null && Config::$debug) {
		print "SHUTDOWN in file:" . $error['file'] . "(" . $error['line'] . ") - Message:" . $error['message'] . '<br />' . PHP_EOL;
		Log::displayFullLog();
	}
    Log::write();
}

?>