<?php
defined('MUDPUPPY') or die('Restricted');

/**
 * The data object for Ruleset
 * This class was auto generated, DO NOT remove or edit # comments
 * #BEGIN MAGIC PROPERTIES
 * @property int id
 * @property string name
 * @property int version
 * @property string ruleSetFile
 * #END MAGIC PROPERTIES
 */ 
class Ruleset extends DataObject {
	
	protected function loadDefaults() {
		// auto-generated code to create columns with default values based on DB schema. DO NOT EDIT.
		// #BEGIN DEFAULTS
		$this->createColumn('id', DATATYPE_INT, NULL, true);
		$this->createColumn('name', DATATYPE_STRING, NULL, false);
		$this->createColumn('version', DATATYPE_INT, NULL, false);
		$this->createColumn('ruleSetFile', DATATYPE_STRING, NULL, false);
		// #END DEFAULTS
		
		// change defaults here if you want user-defined default values
		// $this->updateColumnDefault('column', DEFAULT_VALUE, NOT_NULL);
	}	
	
	public static function getTableName() {
		return 'RuleSets';
	}

	/** 
	 * @param int $id
	 * @return Ruleset
	 */
	public static function get($id) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' WHERE id=?');
		$statement->bindValue(1, $id, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObject = null;
		if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObject = new Ruleset($row);
		}
		return $dataObject;
	}

	/**
	 * @param int $start
	 * @param int $limit
	 * @return array(Ruleset)
	 */
	public static function getAll($start, $limit) {
		$statement = App::getDBO()->prepare('SELECT * FROM ' . self::getTableName() . ' LIMIT ?,?');
		$statement->bindValue(1, $start, PDO::PARAM_INT);
		$statement->bindValue(2, $limit, PDO::PARAM_INT);
		$result = App::getDBO()->query();
		$dataObjects = array();
		while ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
			$dataObjects[] = new Ruleset($row);
		}
		return $dataObjects;
	}
    /**
     * Returns a rule set for a given game
     * @param int $gameId
     * @return (Ruleset)
     */
    public static function getRulesForGame($gameId){
        $statement = App::getDBO()->prepare('SELECT  RuleSets.* ' .
                                            ' FROM Games '.
                                            ' INNER JOIN Events ON Events.id= Games.eventId '.
                                            ' INNER JOIN RuleSets ON Events.ruleSetId = RuleSets.id '.
                                            ' WHERE Games.id = ? ');
        $statement->bindValue(1, $gameId, PDO::PARAM_INT);
        $result = App::getDBO()->query();
        $dataObject = null;
        if ($result && ($row = $result->fetch(PDO::FETCH_ASSOC))) {
            $dataObject = new Ruleset($row);
        }
        return $dataObject;
    }

}

?>