<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for Event
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property string eventName
 * @property int isAsync
 * @property int ruleSetId
 * @property int dateCreated
 * #END MAGIC PROPERTIES
 */ 
class Event extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('eventName', DATATYPE_STRING, NULL, true);
		$this->createColumn('isAsync', DATATYPE_INT, NULL, true);
		$this->createColumn('ruleSetId', DATATYPE_INT, NULL, true);
		$this->createColumn('dateCreated', DATATYPE_DATETIME, NULL, true);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'Events';
	}

	/**
	 * @param int $id
	 * @return Event
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new Event($row);
		}
		return $dataObject;
	}

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(Event)
	 */
	public static function getAll($start, $limit,$minDate) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE dateCreated > ? Order By dateCreated ASC');
		//$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(1, $minDate, PDO::PARAM_STR);
        //$statement->bindValue(3, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new Event($row);
		}
		return $dataObjects;
	}

}

?>