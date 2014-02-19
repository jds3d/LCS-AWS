<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for GameEventType
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property string eventTypeName
 * #END MAGIC PROPERTIES
 */ 
class GameEventType extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventTypeName', DATATYPE_STRING, NULL, true);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'GameEventTypes';
	}

	/**
	 * @param int $id
	 * @return GameEventType
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new GameEventType($row);
		}
		return $dataObject;
	}

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(GameEventType)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new GameEventType($row);
		}
		return $dataObjects;
	}

}

?>