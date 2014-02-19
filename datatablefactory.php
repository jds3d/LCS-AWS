<?php
/**
 * Author: M.PRESS
 * Date: 12/24/13
 * Time: 1:03 PM
 */
/**
 * Class DataTableFactory
 * This class creates HTML tables for datasets
 */
class DataTableFactory {
    private $htmlList;
    private $tabIndex;
    private $deviceIdMap ;
    private $sectionStarted;
    /***
     * Creates a new DataTableFactory
     * @param $firstColumnMap
     */
    public function __construct($firstColumnMap)
    {
        $this->htmlList ="";
        $this->tabIndex = 0;
        $this->sectionStarted = false;
        $this->deviceIdMap = $firstColumnMap;
    }
    /***
     * This function add/ closes a framset to group tables
     * @param $sectionName string the title of the frame set
     * @param $cssName string the name of the css class
     */
    public function addSection($sectionName,$cssName){
        if(!$this->sectionStarted) {;
            $this->sectionStarted = true;
        }
        else {
            $this->htmlList.= "</fieldset>\n";
        }
        $this->htmlList.= "<fieldset class='$cssName'>\n";
        $this->htmlList.= "<legend tabindex='$this->tabIndex'><div>" . $sectionName . " </div> </legend>\n";
		$this->tabIndex++;
	}
    /***
     * This function creates a table for a given query result set
     * @param $metricObject array This is the query result set from the database
     * @param $title  string the title of the tables
     * @param $columnHeaders the names of the title
     * @param bool $replaceFirst boolean if the first column name should be replaced
     */
    public function addTable($metricObject,$title,$columnHeaders,$replaceFirst = false){
		$htmlOutput = "<table>\n";
		$htmlOutput .= "<thead>\n";
		$htmlOutput .= "<tr>\n";
		$htmlOutput .= "<th  class='headerTable' colspan='". count($columnHeaders). "' tabindex='$this->tabIndex' >" . $title  . "</th>\n";
		$this->tabIndex++;
		$htmlOutput .= "</tr>\n";
		$htmlOutput .= "<tr>\n";
		foreach( $columnHeaders  as $headerText) {
			$htmlOutput .= "<th tabindex='$this->tabIndex' >" . $headerText  . "</th>\n";
			$this->tabIndex++;
		}
		$htmlOutput .= "</tr>\n";
		$htmlOutput .= "</thead>\n";
		$htmlOutput .= "<tbody>\n";
		if(count( $metricObject) == 0){
			$htmlOutput .= "<tr>\n";
			$htmlOutput .= "<td colspan='". count($columnHeaders). "' tabindex='$this->tabIndex'  style='text-align:center'> No Data Collected </td>\n";
			$htmlOutput .= "</tr>\n";
			$this->tabIndex++;
		}else{
			foreach($metricObject as $metricRow) {
				$htmlOutput .= "</tr>\n";
				$firstItem = true;
				foreach( $metricRow as  $cellValue) {
					$parsedValue = $cellValue;
					if($replaceFirst && $firstItem){
						if(!array_key_exists($cellValue,$this->deviceIdMap)) {
							$parsedValue = "Player #?";
						}
                        else {
                             $parsedValue = $this->deviceIdMap[$cellValue];
                       }
                    }
                    if($parsedValue == null || $parsedValue == ""){
                        $parsedValue ="N/A";
                    }
                    $htmlOutput .= "<td tabindex='$this->tabIndex'>"  .  ( is_numeric ($parsedValue)?number_format($parsedValue,3,".","") : $parsedValue) ."</td>\n";
                    $this->tabIndex++;
                    $firstItem = false;
                    }
                    $htmlOutput .= "</tr>\n";
               }
           }
            $htmlOutput .= "</tbody>\n";
            $htmlOutput .= "</table>\n ";
            $this->htmlList .= $htmlOutput;
    }

    /**
     * This function returns the HTML output
     * @return string
     */
    public function createHTML(){
        if($this->sectionStarted){
            $this->htmlList.= "</fieldset>\n";
        }
		return  $this->htmlList;
	}
    /***
     * This function deletes all added tables and sections
     */
    public function resetHTML(){
        $this->htmlList ="";
        $this->tabIndex = 0;
        $this->sectionStarted = false;
    }
}