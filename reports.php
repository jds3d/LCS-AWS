<?php
	require_once('app/framework.php');
	require_once('datatablefactory.php');
	require_once('reportsconfig.php');
	// This should change based on url. This way we can send a link based on the event via email
	$eventId =   Request::getInt('eventId',-1,"GET");
	$eventFound = false;
	$reportEventName = "";
	$cssHeader = "reportsHeaderError";
	$headerText = "Sorry, the report you have requested is no longer available. ";
	// Check if the event exists
	if( $eventId != null && Event::get($eventId) != null && $eventId!=-1) {
		$cssHeader = "reportsHeader";
		$headerText = "Digital Leadership Challenge Simulation </br>Full Report On  ";
		$eventFound = true;
		$deviceIdMap = array();
		// Get a List of devices
		$players = User::getPlayersInEvent($eventId);
		$games  = Game::getAllGamesInEvent(100,100,$eventId);
		$gameIdTable = array();
		foreach($games as $currentGame){
			$gameIdTable[$currentGame->id] = $currentGame->gameName;
		}
		// Build Lookup table
		foreach($players as $currentPlayer){
			$deviceIdMap[$currentPlayer->deviceId . "" . $currentPlayer->gameId] = $gameIdTable[$currentPlayer->gameId] . "(Player #". ($currentPlayer->playerNumber +1) . ")";
		}
		// Get the event name
		$reportEventName = Event::get($eventId)->eventName;
		// Create Factory object
		$reportCreator = new DataTableFactory($deviceIdMap);
		
		$table =  AnalyticsAggregator::getEventNames($eventId);
		$columnHeaders = array('Collection Event Name','Number of Players Contributed','Number of Games Contributed ','Data Points Collected');
		$reportCreator->addTable($table,"Metrics Metadata" ,$columnHeaders);
		// Loop over each metric type
		foreach( ReportsConfig::getReportNames() as $metricName){
			$reportCreator->addSection("$metricName Collection  Event","metricField");
			foreach(ReportsConfig::getReport($metricName) as $currentColumn ){
                $keyProperName =  $currentColumn[ReportsConfig::NAME];
                $keyName =  $currentColumn[ReportsConfig::LOOKUP];
                // This is to summary the key if it is an Int
                if($currentColumn[ReportsConfig::TYPE] == "int") {
                    $table = AnalyticsAggregator::getMetricsForEventValue($metricName,false,$keyName,$eventId );
                    $columnHeaders = array('Player Name','Sum','Average','Standard Deviation','Min',"Max");
                    $reportCreator->addTable($table," $keyProperName Metric </br> Player Summary" ,$columnHeaders,true);

                    $table = AnalyticsAggregator::getMetricsForEventValue($metricName,true,$keyName,$eventId );
                    $columnHeaders = array('Game Name','Sum','Average','Standard Deviation','Min',"Max");
                    $reportCreator->addTable($table,"$keyProperName Metric</br> Game Summary" ,$columnHeaders);
                } else {
                    // Categorical Data
                    $table = AnalyticsAggregator::getCategoricalMetrics($metricName,"device", $keyName,$eventId,true );
                    $columnHeaders = array('Player Name',' Value',' Total Count ','Average Time','Min Time', 'Max Time');
                    $reportCreator->addTable($table,"$keyProperName  Metric</br> Player Summary" ,$columnHeaders,true);
                    $table = AnalyticsAggregator::getCategoricalMetrics($metricName,"game", $keyName,$eventId,true);
                    $columnHeaders = array('Game Name',' Value',' Total Count ','Average Time','Min Time', 'Max Time');
                    $reportCreator->addTable($table,"$keyProperName  Metric</br> Game Summary" ,$columnHeaders);
                }
			}
		}
	}
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en" xmlns="http://www.w3.org/1999/html"> <!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<meta name="viewport" content="width=1100, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<title> DLCS Custom Report </title>
		<link rel="stylesheet" type="text/css" href="css/Reports.css">
	</head>
	<body class="background">
		<div <?php  echo "class='$cssHeader'" ?> >
		<div class="headerText" tabindex="0">
				<?php  echo $headerText; ?>
				<span class="eventName"> 
					<?php  
							if ($eventFound) {
								echo "\"$reportEventName\""; 
							} 
					?> 
				</span> 
			</div>
		</div>
			<?php
					if($eventFound){
						echo '<div class="reportsBody">'.$reportCreator->createHTML() . '</div>';
					}
			?>
		<div class="footer" >  
			<?php if($eventFound){ echo 'Report Created On ' . date("l jS \of F Y");}?> 
		</div>
	</body>
</html>