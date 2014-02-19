<?php
/**
 * This Module collects data for an event.
 * Extra methods are used to create reports.
 * User: M.Press
 * Date: 12/6/13
 * Time: 4:39 PM
 */

class AnalyticsModule extends  Module{
    const PARAMS_LOCATION = 'POST';

    /***
     *  Gets the names for the metrics
     * @return array (Json)
     */
    public function  action_getEventNames(){
        $eventId = Request::getInt('eventId',-1,AnalyticsModule::PARAMS_LOCATION);
        if($eventId == -1 ){
            return array('Action'=>'Failed');
        }
        $names = AnalyticsAggregator::getEventNames($eventId);
        return array("MetricNames"=>DataObject::objectListToArrayList($names));
    }
    /**
     * Saves the event.
     * @return array (Json)
     */
    public function  action_saveEvent(){
        $gameId = Request::getInt('gameId',-1,AnalyticsModule::PARAMS_LOCATION);
        $name = Request::get('eventName','',AnalyticsModule::PARAMS_LOCATION);
        $deviceId = Request::get('deviceId','',AnalyticsModule::PARAMS_LOCATION);
        $updateRecord = Request::get('update','',AnalyticsModule::PARAMS_LOCATION);

        if($gameId == -1 || $name == '' || $deviceId == '' ){
            return array('Action'=>'Failed');
        }
        $aggregator = new AnalyticsAggregator();
        $aggregator->eventName = $name;
        $aggregator->gameId = $gameId;
        $aggregator->deviceId = $deviceId;
        // Loop over the keys to save if they exist!
        for( $keyCount = 1; $keyCount <= AnalyticsAggregator::MAX_KEYS; $keyCount++){
           $newKey =  Request::get("keyValue$keyCount",'',AnalyticsModule::PARAMS_LOCATION);
            if( $newKey != '' ){
                $aggregator->{"keyValue$keyCount"} = $newKey;
            }
        }
        if($updateRecord == 'update' ) {
            AnalyticsAggregator::updateStartingMetric($aggregator);
        }
        else {
            $aggregator->startTime = Time();
            $aggregator->save();
        }
        return array('Action'=>'Success');
    }
    /**
     * Gets the metrics for a given event name
     * @return array (Json)
     */
    public function action_getMetrics(){
        $name = Request::get('eventName','',AnalyticsModule::PARAMS_LOCATION);
        $metrics =  AnalyticsAggregator::getMetricsByEventName($name);
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }
    /**
     *Gets the metrics for a given deviceId
     * @return array (Json)
     */
    public function action_getUserMetrics(){
        $deviceId = Request::get('deviceId','',AnalyticsModule::PARAMS_LOCATION);
        $metrics =  AnalyticsAggregator::getMetricsByDeviceId($deviceId);
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }

    /**
     *Gets the metrics for a given gameId
     * @return array (Json)
     */
    public function action_getGameMetrics(){
        $gameId = Request::get('gameId','',AnalyticsModule::PARAMS_LOCATION);
        $metrics =  AnalyticsAggregator::getMetricsByGameId($gameId);
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }


    /**
     *Gets the metrics for a given eventId
     * @return array (Json)
     */
    public function action_getEventMetrics(){
        $eventId = Request::get('eventId','',AnalyticsModule::PARAMS_LOCATION);
        $metrics =  AnalyticsAggregator::getMetricsByEventId($eventId);
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }

    /**
     * Gets the total count for each metric type
     * @return array (Json)
     */
    public function action_getMetricKeyCounts(){
        $eventName = Request::get('eventName','',AnalyticsModule::PARAMS_LOCATION);
        $searchBy = Request::get('searchBy','none',AnalyticsModule::PARAMS_LOCATION);
        $searchValue = Request::get('value','',AnalyticsModule::PARAMS_LOCATION);

        // Input must at least an event name or user Id
        if(   $eventName == '' && $searchBy  !='none' && $searchValue != '' ){
            return array('Action'=>'Failed');
        }
        switch($searchBy){
            case "game" :
            case "event" :
                $searchValue = Request::getInt('value',0,AnalyticsModule::PARAMS_LOCATION);
                break;
            case "device" :
                break;
            default :
                $searchBy = "none";
        }

        $metrics =  AnalyticsAggregator::getKeyValuesCount($eventName,$searchBy,$searchValue);
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }
    /**
     * Gets the SUM, Average, Standard Dev, Min, and Max of the column
     *
     * @return array (Json)
     */
    public function action_getMetricsKeyValueStatistics(){
        $eventName = Request::get('eventName','',AnalyticsModule::PARAMS_LOCATION);
        $columnName = Request::get('columnName','',AnalyticsModule::PARAMS_LOCATION);
        $groupBy = Request::get('groupBy','',AnalyticsModule::PARAMS_LOCATION);
        $selectionEvent = false;
        $selectionBool = false;
        if($groupBy == "game" ){
            $selectionBool = true;
        }
        if($groupBy == "event" ){
            $selectionEvent = true;
        }
        if($columnName == ''  || $eventName == ''  ){
            return array('Action'=>'Failed');
        }
        $metrics =  AnalyticsAggregator::getMetricsForValue($eventName,$selectionBool,$columnName,$selectionEvent );
        return array("Metrics"=>DataObject::objectListToArrayList($metrics));
    }


    /**
     * This methods grants Permissions for the module.
     * @param string $method name
     * @param array $input
     * @return array the permissions
     */
    public function getRequiredPermissions($method, $input) {
        // Right Now anyone can use it!
        return array();
    }
    //==========================================================================
}