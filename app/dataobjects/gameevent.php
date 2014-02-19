<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for GameEvent
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property int eventTypeId
 * @property int gameId
 * @property int userId
 * @property string data
 * @property int date
 * #END MAGIC PROPERTIES
 */ 
class GameEvent extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventTypeId', DATATYPE_INT, NULL, true);
		$this->createColumn('gameId', DATATYPE_INT, NULL, true);
		$this->createColumn('userId', DATATYPE_INT, NULL, true);
		$this->createColumn('data', DATATYPE_STRING, NULL, true);
		$this->createColumn('date', DATATYPE_DATETIME, 'CURRENT_TIMESTAMP', true);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'GameEvents';
	}

	/**
	 * @param int $id
	 * @return GameEvent
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new GameEvent($row);
		}
		return $dataObject;
	}

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(GameEvent)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new GameEvent($row);
		}
		return $dataObjects;
	}
  
  	public static function getNewEvents($eventId,$gameId) {
		$statement = App::getDBO()->prepare('SELECT * FROM '
                                                . self::getTableName() .
                                                ' WHERE id>? AND gameId=?  Order By id ASC'
                                            );
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
		$statement->bindValue(2, $gameId, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new GameEvent($row);
		}
		return $dataObjects;
	}
}

?>