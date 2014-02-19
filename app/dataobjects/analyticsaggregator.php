<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for AnalyticsAggregator
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property string eventName
 * @property string keyValue1
 * @property string keyValue2
 * @property string keyValue3
 * @property int gameId
 * @property int startTime
 * @property string deviceId
 * @property int endTime
 * #END MAGIC PROPERTIES
 */ 
class AnalyticsAggregator extends DataObject {
    //Modify this constant to change the number of keyValues!
    const MAX_KEYS = 3;
    const INVALID_KEY = 'Invalid Key';
    const START_KEY = "Start";
    const END_KEY = "End";
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventName', DATATYPE_STRING, NULL, true);
		$this->createColumn('keyValue1', DATATYPE_STRING, NULL, false);
		$this->createColumn('keyValue2', DATATYPE_STRING, NULL, false);
		$this->createColumn('keyValue3', DATATYPE_STRING, NULL, false);
		$this->createColumn('gameId', DATATYPE_INT, NULL, true);
		$this->createColumn('startTime', DATATYPE_DATETIME, NULL, true);
		$this->createColumn('deviceId', DATATYPE_STRING, NULL, true);
		$this->createColumn('endTime', DATATYPE_DATETIME, NULL, false);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'AnalyticsAggregator';
	}

	/**
	 * @param int $id
	 * @return AnalyticsAggregator
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new AnalyticsAggregator($row);
		}
		return $dataObject;
	}

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(AnalyticsAggregator)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new AnalyticsAggregator($row);
		}
		return $dataObjects;
	}


    /**
     * Gets all metrics of a type.
     * @param string $name
     * @return array(AnalyticsAggregator)
     */
    public static function getMetricsByEventName($name) {
        $statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE eventName=?');
        $statement->bindValue(1, $name, PDO::PARAM_STR);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = new AnalyticsAggregator($row);
        }
        return $dataObjects;
    }


    /**
     * Gets all metrics from a device.
     * @param int $deviceId
     * @return array(AnalyticsAggregator)
     */
    public static function getMetricsByDeviceId($deviceId) {
        $statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deviceId=?');
        $statement->bindValue(1, $deviceId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = new AnalyticsAggregator($row);
        }
        return $dataObjects;
    }


    /**
     * Gets all metrics from a game.
     * @param int $gameId
     * @return array(AnalyticsAggregator)
     */
    public static function getMetricsByGameId($gameId) {
        $statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE gameId=? ');
        $statement->bindValue(1, $gameId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = new AnalyticsAggregator($row);
        }
        return $dataObjects;
    }


    /**
     * Gets all metrics from a game.
     * @param int $eventId
     * @return array(AnalyticsAggregator)
     */
    public static function getMetricsByEventId($eventId) {
        $statement = App::getDBO()->prepare(
                                            'SELECT Games.gameName,  AnalyticsAggregator.* FROM '
                                             . self::getTableName() .
                                            ' INNER JOIN Games ON Games.id = gameId '.
                                            ' WHERE Games.eventId=? ');
        $statement->bindValue(1, $eventId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = new Analyticsaggregator($row);
        }
        return $dataObjects;
    }



    /**
     * Gets the total count for each category for an event
     * if the device id is not '' then it is included in the where clause
     * @param string $searchType the type of search
     * @param int $searchValue
     * @param string $eventName
     * @return array(AnalyticsAggregator)
     */
    public static function getKeyValuesCount($eventName,$searchType,$searchValue) {
        $whereClause  = "  ";
        $selectStatement  = " deviceId ";
        switch($searchType){
            case "device" :
                $whereClause = " AND deviceId=? ";
                break;
            case "game" :
                $whereClause = " AND gameId=? ";
                break;
            case "event" :
                $whereClause = " AND Games.eventId=? ";
                break;
            case "none"  :
                break;
            default :
                $searchType = "none";
                break;
        }

        foreach(AnalyticsAggregator::getKeys() as $key){
            $selectStatement .=  ", $key , COUNT($key) ";
        }
        $groupStatement =  ' GROUP BY ';
        $skip = true;
        foreach(AnalyticsAggregator::getKeys() as $key){
            $groupStatement .=   ($skip ? '' : ' , ' ) . "  $key  ";
            $skip = false ;
        }
        $statement = App::getDBO()->prepare(
                                            ' SELECT '.$selectStatement .
                                            ' FROM ' . self::getTableName() .
                                            ' INNER JOIN Games ON Games.Id =  gameId' .
                                            ' WHERE eventName=? ' .
                                            $whereClause . " " .
                                             $groupStatement
                                            );
        $statement->bindValue(1, $eventName, PDO::PARAM_STR);
        if( $searchType == 'deviceId') {
            $statement->bindValue(2, $searchValue, PDO::PARAM_STR);
        }
        else if( $searchType != 'none') {
            $statement->bindValue(2, $searchValue, PDO::PARAM_INT);
        }
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] =  ($row);
        }
        return $dataObjects;
    }

	
	
	
	/**
     * Gets the total count for each category for an event
     * if the device id is not '' then it is included in the where clause
     * @param string $eventName
     * @param string $searchType
     * @param int $eventId
     * @param string $valueName
     * @param boolean $includeTime
     * @return array(AnalyticsAggregator)
     */
    public static function getCategoricalMetrics($eventName,$searchType,$valueName,$eventId,$includeTime = false) {
		$validKey = AnalyticsAggregator::getKey($valueName);
        // Bad name was passed return nothing!
        if($validKey == AnalyticsAggregator::INVALID_KEY){
            return array();
        }
        $groupBy  = " GROUP BY ";
        $selectStatement  = "  deviceId ";
        switch($searchType){
            case "device" :
                $groupBy .= " deviceId , $validKey ";
				$selectStatement =  "CONCAT(deviceId,Games.id), $validKey , COUNT($validKey)  ";
                break;
            case "game" :
                $groupBy .= " gameId  , $validKey ";
				$selectStatement =  "Games.gameName, $validKey , COUNT($validKey) ";
                break;
            case "none"  :
                break;
            default :
                $searchType = "none";
                break;
        }
		$timer = ' TIME_TO_SEC(TIMEDIFF(AnalyticsAggregator.endTime,AnalyticsAggregator.startTime)) ';
		$selectStatement .=  $includeTime ?   ", AVG ($timer) , MIN($timer), MAX($timer)" : '';
        $statement = App::getDBO()->prepare(
                                            ' SELECT '.$selectStatement .
                                            ' FROM ' . self::getTableName() .
                                            ' INNER JOIN Games ON Games.Id =  gameId ' .
                                            ' WHERE eventName=? and Games.eventId = ? ' .
                                             $groupBy
                                            );
        $statement->bindValue(1, $eventName, PDO::PARAM_STR);
        $statement->bindValue(2, $eventId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] =  ($row);
        }
        return $dataObjects;
    }
	
	
	
    /**
     * This function returns Metrics for a single value.
     * if groupByName is true the the results are group by the game
     * otherwise it is grouped by deviceId
     * @param string $eventName
     * @param  boolean $groupByGame
     * @param string $valueName
     * @param boolean $groupByEvent
     * @return array (JSON)
     */
    public static function getMetricsForValue($eventName,$groupByGame ,$valueName,$groupByEvent){
        $validKey = AnalyticsAggregator::getKey($valueName);
        // Bad name was passed return nothing!
        if($validKey == AnalyticsAggregator::INVALID_KEY){
            return array();
        }
        $groupByVar =  ($groupByGame) ? "Games.gameName" : "CONCAT(deviceId,Games.id)";
        $groupByVar =  ($groupByEvent) ? "Events.eventName": $groupByVar;

        // Note that the selected column must contain good data!
        $statement = App::getDBO()->prepare(
            " SELECT  $groupByVar AS Name , " .
            " SUM(CAST($validKey as   DECIMAL(10,6))) as Sum, ".
            " AVG(CAST($validKey as   DECIMAL(10,6))) as Average, " .
            " STD(CAST($validKey as   DECIMAL(10,6))) as StandardDeviation, ".
            " MIN(CAST($validKey as   DECIMAL(10,6))) as Min, ".
            " MAX(CAST($validKey as   DECIMAL(10,6))) as Max ".
            ' FROM '  .  self::getTableName() .
            ' INNER JOIN Games ON Games.id = AnalyticsAggregator.gameId ' .
            ' INNER JOIN Events ON Events.id = Games.eventId ' .
            " WHERE AnalyticsAggregator.eventName=?  GROUP BY $groupByVar "
        );
        $statement->bindValue(1, $eventName, PDO::PARAM_STR);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = $row;
        }
        return $dataObjects;
    }
	
	
	/**
     * This function returns Metrics for a single value.
     * if groupByName is true the the results are group by the game
     * otherwise it is grouped by deviceId
     * @param string $eventName
     * @param  boolean $groupByGame
     * @param string $valueName
	 * @param int $eventId
     * @return array (JSON)
     */
    public static function getMetricsForEventValue($eventName,$groupByGame ,$valueName,$eventId){
        $validKey = AnalyticsAggregator::getKey($valueName);
        // Bad name was passed return nothing!
        if($validKey == AnalyticsAggregator::INVALID_KEY){
            return array();
        }
        $groupByVar =  ($groupByGame) ? "Games.gameName" : "CONCAT(deviceId,Games.id)";

        // Note that the selected column must contain good data!
        $statement = App::getDBO()->prepare(
            " SELECT  $groupByVar AS Name , " .
            " SUM(CAST($validKey as   DECIMAL(10,6))) as Sum, ".
            " AVG(CAST($validKey as   DECIMAL(10,6))) as Average, " .
            " STD(CAST($validKey as   DECIMAL(10,6))) as StandardDeviation, ".
            " MIN(CAST($validKey as   DECIMAL(10,6))) as Min, ".
            " MAX(CAST($validKey as   DECIMAL(10,6))) as Max ".
            ' FROM '  .  self::getTableName() .
            ' INNER JOIN Games ON Games.id = AnalyticsAggregator.gameId ' .
            " WHERE AnalyticsAggregator.eventName=? AND Games.eventId=? GROUP BY $groupByVar"
        );
        $statement->bindValue(1, $eventName, PDO::PARAM_STR);
		$statement->bindValue(2, $eventId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = $row;
        }
        return $dataObjects;
    }
	
	
	
	
    /***
     * Groups The Metric Names to return a list of metrics collected
	 * @param int $eventId
     * @return array (JSON) 
     */
	public static  function  getEventNames($eventId){
         $statement = App::getDBO()->prepare(
            " SELECT  eventName, COUNT( DISTINCT  AnalyticsAggregator.deviceId) AS UserTotals, COUNT(DISTINCT AnalyticsAggregator.gameId) As GameTotals, COUNT(*) AS Totals " .
            ' FROM '  .  self::getTableName() .
		    ' INNER JOIN Games ON Games.id = AnalyticsAggregator.gameId ' .
			' WHERE Games.eventId=? ' .
            " GROUP BY eventName " 
        );
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = $row;
        }
        return $dataObjects;
    }

    /**
     * The method safely checks the string to prevent improper SQL statements
     * @param  string $name the name of the key
     * @return string the name of the key
     */
    public static function getKey($name){

        for($keyLoop = 1; $keyLoop <=AnalyticsAggregator::MAX_KEYS; $keyLoop++){
            if('keyValue'.$keyLoop == $name){
                return 'keyValue'.$keyLoop;
            }
        }
        return  AnalyticsAggregator::INVALID_KEY;
    }


    /*
     * This function returns the names of keyValues in the table
     * @return array (string)
     */
    public  static function  getKeys(){
        $keys = array();
        for($keyLoop = 1; $keyLoop <=AnalyticsAggregator::MAX_KEYS; $keyLoop++){
            $keys[$keyLoop] = 'keyValue'.$keyLoop;
        }
        return $keys;
    }

    /**
     * This method finds the metric record in question and updates it.
     * @param $metric (AnalyticsAggregator) the metric to be updated
     */
    public static function updateStartingMetric($metric){
        $whereClause = "";
        foreach(AnalyticsAggregator::getKeys() as $key){
			Log::add($metric->{$key} );
            if( !is_null( $metric->{$key} )) {
			
				$whereClause.=  " AND $key = ? ";
			}
        }
        // First step is to find the id
        $statement = App::getDBO()->prepare(
            " SELECT  *  " .
            " FROM "  .  self::getTableName() .
            " WHERE " .
                  " endTime IS NULL  AND " .
                  " eventName = ?   AND " .
                  " gameId = ?      AND " .
                  " deviceId = ?       "  .
                  $whereClause .
             " ORDER BY " .
                    " startTime DESC ".
             " LIMIT 1 "
        );
        $statement->bindValue(1, $metric->eventName, PDO::PARAM_STR);
        $statement->bindValue(2, $metric->gameId, PDO::PARAM_INT);
        $statement->bindValue(3, $metric->deviceId, PDO::PARAM_STR);
        $nextKey = 4;
        foreach(AnalyticsAggregator::getKeys() as $key){
		     if( !is_null( $metric->{$key} )) {
				$statement->bindValue($nextKey, $metric->{$key}, PDO::PARAM_STR);
				$nextKey++;
			}
        }
        $result = App::getDBO()->query();
        $dataObjects = array();
        while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObjects[] = new AnalyticsAggregator($row);
        }
        // Object was found add the end time
        if( count($dataObjects) == 1 ){
            $aggregator = $dataObjects[0];
            $aggregator->endTime = Time();
            $aggregator->save();
        }
    }
}

?>