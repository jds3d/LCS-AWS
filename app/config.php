<?php
defined('MUDPUPPY') or die('Restricted');

define('APP_VERSION', '1.1.0');

// Base configuration
class Config {
    // Database Settings
    public static $dbProtocol = 'mysql:host=%s;dbname=%s';
    public static $dbHost = 'localhost';
    public static $dbDatabase = 'LCS';
    public static $dbUser = 'root'; 
    public static $dbPass = '';
    // This is just for local testing!!!
    public static $adminPass = 'localPASS';
    public static $ruleSetVersion = 1;
    // Site Configuration
    public static $appTitle = 'LCS APP';
    public static $docroot = ''; // must NOT have a trailing '/'
    public static $sitedirectory = '/'; // must be / if site is in root.  must have leading and trailing / otherwise
    public static $timezone = 'America/New_York';
    public static $dateFormat = 'd M Y H:i:s \\G\\M\\TO';

    // Debugging
    public static $debug = true;
    public static $logQueries = true;
    public static $logLevel = LOG_LEVEL_ALWAYS;
}
 
// Production-specific overrides
if (strcasecmp($_SERVER["SERVER_NAME"], 'localhost') != 0 && $_SERVER["SERVER_ADDR"] != '127.0.0.1' && strncmp($_SERVER["SERVER_ADDR"], '192.168', 7) != 0) {
    Config::$dbHost = $_SERVER['RDS_HOSTNAME'];
    Config::$dbUser = $_SERVER['RDS_USERNAME'];
    Config::$dbPass = $_SERVER['RDS_PASSWORD'];
    Config::$adminPass = $_SERVER['ADMIN_PASSWORD'];
    Config::$debug = false;
    Config::$logQueries = false;
}
?>