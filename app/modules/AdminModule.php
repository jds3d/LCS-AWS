<?php
/**
 *
 * This class handles all web based admin functions.
 * Author: M.Press
 * Date: 12/5/13
 * Time: 4:29 PM
 */
class AdminModule extends  Module{
    const MAX_FILE_UPLOAD_SIZE = 1000000;
    const UPLOAD_TYPE = 'text/plain';
    /***
     * Uploads a new json file for the DLCS
     * @return array success of failure json to web page
     */
    function action_addConfigFile(){
        $adminPassCode =  Request::get('adminPassCode',-1,'POST');
        $uploadName  =  Request::get('fileName',-1,'POST');
        if( $adminPassCode != Config::$adminPass) {
            return array('Action'=>'Bad Passcode');
        }
        // Cant have a blank name!
        if($uploadName == "" ){
            return array('Action'=>'Failed');
        }
        // Sanity check, make sure the file is not terrible
        if ($_FILES['configFile']["error"] > 0 )
        {
            return array('Action'=>'Failed');
        }
        else if($_FILES['configFile']["type"] != AdminModule::UPLOAD_TYPE)
        {
            return array('Action'=>'Failed');
        }
        else if( $_FILES['configFile']["size"] > AdminModule::MAX_FILE_UPLOAD_SIZE)
        {
            return array('Action'=>'Failed');
        }
        $handle = fopen($_FILES['configFile']["tmp_name"], "rb");
        $contents = fread($handle, filesize( $_FILES['configFile']["tmp_name"]));
        fclose($handle);
 
        // Make a new RuleSet
        $ruleSetNew = new Ruleset();
        $ruleSetNew->name = $uploadName;
        $ruleSetNew->version = Config::$ruleSetVersion;
        $ruleSetNew->ruleSetFile = $contents;
        $ruleSetNew->save();
        // Clean Up the file we don't want it on an EC2

        unlink($_FILES['configFile']["tmp_name"]);
        // The RuleSet was added report back to webpage!
        return array('Action'=>'Success');
    }

    //======================Required Methods===================================
    // These method are required for mudpuppy to work
    //=========================================================================
    public function getRequiredPermissions($method, $input) {
        // TODO: add permissions as needed
        return array();
    }
    //==========================================================================

} 