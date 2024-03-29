<?php
/**
 * Created By: Levi Lansing
 * Date: 7/8/13
 */

class ErrorLogModule extends DataObjectModule {

    public function __construct() {
        // don't write the log for this request
        Log::dontWrite();

        // disable cache
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache"); // HTTP/1.0
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

        parent::__construct('ErrorLog');
    }

    public function getRequiredPermissions($method, $input) {
        if (!Config::$debug)
            return array('SuperAdmin');
        return array();
    }

    protected function retrieveDataObjects($params) {
        return parent::retrieveDataObjects($params);
    }

    protected function getStructureDefinition() {
        return parent::getStructureDefinition();
    }

    protected function isValid($object) {
        return true;
    }

    protected function sanitize($object) {
        return $object;
    }

    public function action_pull() {
        Log::dontWrite();
        $lastId = Request::getInt('lastId', -1);
        if ($lastId >= 0) {
            $results = ErrorLog::getByFields(array(), "id > $lastId ORDER BY id ASC");
        } else {
            $results = ErrorLog::getByFields(array(), "id > $lastId ORDER BY id DESC LIMIT 15");
            $results = array_reverse($results);
        }
        if (!empty($results)) {
            return DataObject::objectListToArrayList($results);
        }
        return array();
    }

    public function action_waitForNext() {
        Log::dontWrite();
        session_write_close();
        set_time_limit(60*5);

        $pipe = 'app/cache/mudpuppy_errorLogPipe';
        if (!file_exists($pipe)) {
            posix_mkfifo($pipe, 0777);
        }

        $lastId = Request::getInt('lastId', -1);
        if ($lastId >= 0) {
            $results = ErrorLog::getByFields(array(), "id > $lastId ORDER BY id ASC");
            if (!empty($results)) {
                return DataObject::objectListToArrayList($results);
            }
        }

        $fp = fopen($pipe, 'r+');
        stream_set_timeout($fp, 60);

        $read = array($fp);
        $write = null;
        $except = null;
        $triggered = stream_select($read, $write, $except, 30);
        fclose($fp);
        unlink($pipe);

        if ($triggered && $lastId == -1) {
            return array(ErrorLog::getLast()->toArray());
        } else if ($lastId >= 0) {
            $results = ErrorLog::getByFields(array(), "id > $lastId ORDER BY id ASC");
            if (!empty($results)) {
                return DataObject::objectListToArrayList($results);
            }
        }
        Log::add('nothing');


        return array();
    }

    public function action_getLast() {
        return ErrorLog::getLast()->toArray();
    }

    public function action_trigger() {
        $pipe = 'app/cache/mudpuppy_errorLogPipe';
        if (!file_exists($pipe)) {
            return;
        }

        $fp = fopen($pipe, 'r+');
        stream_set_timeout($fp, 10);
        fwrite($fp, '1');
        fclose($fp);
        unlink($fp);
    }

    public function action_clearLog() {
        if (Config::$debug) {
            ErrorLog::deleteAll();
        }
    }
}