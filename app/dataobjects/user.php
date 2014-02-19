<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for User
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property int eventId
 * @property int gameId
 * @property string deviceId
 * @property int isFacilitator
 * @property int isGameMaster
 * @property int lastUpdate
 * @property int playerNumber
 * #END MAGIC PROPERTIES
 */ 
class User extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventId', DATATYPE_INT, NULL, true);
		$this->createColumn('gameId', DATATYPE_INT, NULL, false);
		$this->createColumn('deviceId', DATATYPE_STRING, NULL, true);
		$this->createColumn('isFacilitator', DATATYPE_INT, NULL, true);
		$this->createColumn('isGameMaster', DATATYPE_INT, NULL, true);
		$this->createColumn('lastUpdate', DATATYPE_DATETIME, NULL, false);
		$this->createColumn('playerNumber', DATATYPE_INT, NULL, false);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'Users';
	}

	/**
	 * @param int $id
	 * @return User
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new User($row);
		}
		return $dataObject;
	}
	
	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(User)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
	public static function getPlayer($deviceId,$gameId) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deviceId=? AND gameId=?');
		$statement->bindValue(1, $deviceId, PDO::PARAM_STR);
		$statement->bindValue(2, $gameId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new User($row);
		}
		return $dataObject;
	}
	
	public static function getGameMaster($eventId,$deviceId) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deviceId=? AND eventId=? AND isGameMaster=TRUE');
		$statement->bindValue(1, $deviceId, PDO::PARAM_STR);
		$statement->bindValue(2, $eventId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new User($row);
		}
		return $dataObject;
	}
	public static function getFacilitator($eventId,$deviceId) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE deviceId=? AND eventId=? AND isFacilitator=1');
		$statement->bindValue(1, $deviceId, PDO::PARAM_STR);
		$statement->bindValue(2, $eventId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new User($row);
		}
		return $dataObject;
	}
	
	public static function getPlayersInGame($gameId){
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE gameId=? AND isFacilitator=FALSE ORDER BY playerNumber  ASC');
		$statement->bindValue(1, $gameId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	public static function getFacilitaorsInGame($gameId){
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE gameId=? AND isFacilitator=1');
		$statement->bindValue(1, $gameId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
	public static function getGameMastersInEvent($eventId){
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE eventId=? AND isGameMaster=TRUE');
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
	
	public static function getFacilitaorsInEvent($eventId){
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE eventId=?  AND isFacilitator=1');
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	public static function getActivePlayers($gameId,$timeout){
		$newTime = gmdate('Y-m-d H:i:s',time());
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE gameId=?  AND isFacilitator=FALSE AND UNIX_TIMESTAMP( ?) - UNIX_TIMESTAMP(lastUpdate) <? ORDER BY playerNumber  ASC');
		$statement->bindValue(1, $gameId, PDO::PARAM_INT);
		$statement->bindValue(2, $newTime, PDO::PARAM_STR);
		$statement->bindValue(3, $timeout, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
	public static function getActiveFacilitaors($gameId,$timeout){
		$newTime = gmdate('Y-m-d H:i:s',time());
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE gameId=?  AND isFacilitator=TRUE AND UNIX_TIMESTAMP( ?) - UNIX_TIMESTAMP(lastUpdate) <?');
		$statement->bindValue(1, $gameId, PDO::PARAM_INT);
		$statement->bindValue(2, $newTime, PDO::PARAM_STR);
		$statement->bindValue(3, $timeout, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
	public static function getPlayersInEvent($eventId){
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE eventId=? AND isGameMaster=FALSE AND isFacilitator=FALSE' );
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new User($row);
		}
		return $dataObjects;
	}
	
}

?>