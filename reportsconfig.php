<?php
/**
 * Author: M.PRESS
 * Date: 12/24/13
 * Time: 1:12 PM
 */
/**
 * Class ReportsConfig
 * This class conatins helper functions to create a report
 */
class ReportsConfig {
    private  static $reportMap  = array();
    const TYPE ="type";
    const NAME ="name";
    const LOOKUP ="lookup";
    /***
     * Adds the report to the config static object
     * @param $keyName the name of the report
     * @param $setConfig the report to be added
     */
    public static function addReport($keyName,$setConfig){
        ReportsConfig::$reportMap[$keyName] = $setConfig;
    }

    /***
     * Gets the report for a given name
     * @param $reportName the name of the report
     * @return mixed
     */
    public static  function getReport($reportName){
        return ReportsConfig::$reportMap[$reportName];
    }
    /***
     * returns the list of current Reports
     * @return array (string)
     */
    public static  function getReportNames(){
        return  array_keys(ReportsConfig::$reportMap);
    }
    /***
     * Creates A Column for a report
     * @param string $type the type of column
     * @param string $name the name of the column
     * @param  int $index the index in the database
     * @return array
     */
    public static function createColumn($type,$name,$index){
        return array (ReportsConfig::TYPE => $type , ReportsConfig::NAME => $name, ReportsConfig::LOOKUP=>"keyValue$index");
    }
}


/***
 *  Edit the following lines below to create / edit reports
 *
 * BEGIN REPORTS
 */

ReportsConfig::addReport ("UsedMentorPersonal",
    array(
        ReportsConfig::createColumn("int","Mentor Points",1),
        ReportsConfig::createColumn("int","Personal Points",2),
        ReportsConfig::createColumn("int","Tier Level",3),
    )
);

ReportsConfig::addReport ("SpaceTime",
    array(
        ReportsConfig::createColumn("string","Space Type",1),
    )
);
ReportsConfig::addReport ("RiskLevelAccepted",
    array(
        ReportsConfig::createColumn("int","Risk Level",1),
    )
);
ReportsConfig::addReport ("EventMarkersAdded",
    array(
        ReportsConfig::createColumn("string","Event Marker Type",1)
    )
);

/***
 * End of Reports
 */