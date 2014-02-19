<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for Game
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property int eventId
 * @property string gameName
 * @property int activePlayerId
 * @property int currentStatusId
 * @property int startTime
 * @property int endTime
 * @property string gameState
 * @property int maxPlayers
 * #END MAGIC PROPERTIES
 */ 
class Game extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventId', DATATYPE_INT, NULL, true);
		$this->createColumn('gameName', DATATYPE_STRING, NULL, true);
		$this->createColumn('activePlayerId', DATATYPE_INT, NULL, false);
		$this->createColumn('currentStatusId', DATATYPE_INT, NULL, true);
		$this->createColumn('startTime', DATATYPE_DATETIME, NULL, false);
		$this->createColumn('endTime', DATATYPE_DATETIME, NULL, false);
		$this->createColumn('gameState', DATATYPE_STRING, NULL, true);
		$this->createColumn('maxPlayers', DATATYPE_INT, NULL, true);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	 
	public static function getTableName() {
		return 'Games';
	}

	/**
	 * @param int $id
	 * @return Game
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new Game($row);
		}
		return $dataObject;
	}
	

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(Game)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new Game($row);
		}
		return $dataObjects;
	}

	public static function getAllGamesInEvent($start, $limit,$eventId) {
		$statement = App::getDBO()->prepare('SELECT * FROM '
                                            . self::getTableName() .
                                            ' WHERE eventId=? ORDER BY id ASC');
		$statement->bindValue(1, $eventId, PDO::PARAM_INT);
		///$statement->bindValue(2, $start, PDO::PARAM_INT);
		//$statement->bindValue(3, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new Game($row);
		}
		return $dataObjects;
	}

	/**
	 * Only one thread may modify the game at a time.
	 * This will Block other threads access the gameID untill the thread is complete!
	 */
	static function  lockGame (){
		$statement = App::getDBO()->prepare('LOCK TABLES GAMES WRITE');
		$result = App::getDBO()->query();
	}

}

?>