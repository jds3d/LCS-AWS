<?php

//=========================EventsModule======================================
//
//	Author:        M.Press
//  Creation Date: 8/20/2013
//  About :        This module contains the REST server for Unity LCS
//
//===========================================================================
defined('MUDPUPPY') or die('Restricted');


//====================== MODULE CONST VARS ==================================
class GameState {
	const LOADING = -1;
    // New State 
    const WAITING_FOR_PLAYER_CREATION = 8;
	const RUNNING =  0;
	const WAITING_FOR_PLAYER = 1;
	const WAITING_FOR_FACILITATOR = 2;
	const CHANGE_TURN = 3;
	const PAUSED = 4;
	const RESUME  = 5;
	const GAME_OVER  = 6;

		
}
class ServerSettings {
	const PARAMS_LOCATION = 'POST';
	const MAX_QUERY =  99999;
	const MAX_PLAYERS = 4;
	const MIN_PLAYERS = 2;
	const PLAYER_TIMEOUT = 30;
    const MAX_DAYS = 9000;
    const DEFAULT_DAYS = 30;
}

/**
 * TODO : ASK JEFF FOR PROPER JSON
 */

class ServerMessages {
	const PLAYER_JOINED = '[1,1]';
	const WAITING_FOR_PLAYER = '[2,1]';
	const WAITING_FOR_FACILITATOR = '[3,1]';
    const WAITING_FOR_PLAYER_CREATION = '[8,1]';
	const CHANGE_TURN = '[4,1]';
	const PAUSED = '[5,1]';
	const RESUME = '[6,1]';
	const GAME_OVER = '[7,1]';
}
/**
 * TODO: GIVE THESE VALUES TO JEFF
 */
class ServerMessageType {
	const SERVER_MESSAGE = 7;
}



class EventsModule extends Module {


	//=======================PUBLIC API==================================
	// Anything can call these functions
	//===================================================================




	//=======================createEvent===================================
	// REST CALL: URL\createEvent
	// Description : Returns all events in the System
	//
	//===================================================================
	function action_createEvent(){
		$isAsync = Request::getBool('isAsync',0,ServerSettings::PARAMS_LOCATION);
		$name = Request::get('name','',ServerSettings::PARAMS_LOCATION);
        $newRuleSetId = Request::getInt('ruleSetId',-1,ServerSettings::PARAMS_LOCATION);
		if($name == '' || $newRuleSetId == -1 || Ruleset::get($newRuleSetId) == null){
			return array('Action'=>'Failed');
		}
		$newEvent = new Event();
		$newEvent->isAsync = $isAsync;
		$newEvent->eventName = $name;
        $newEvent->ruleSetId = $newRuleSetId;
        $newEvent->dateCreated = time();
		$newEvent->checkSave();
		return array('Action'=>'Success');
	}

	//=======================getEvents===================================
	// REST CALL: URL\getEvents
	// Description : Returns all events in the System
	//
	//===================================================================
	function action_getEvents(){

        $days = Request::getInt('days',0,ServerSettings::PARAMS_LOCATION);
        Log::add($days);
        if( $days  <= 0 || $days >= ServerSettings::MAX_DAYS){
            $days = ServerSettings::DEFAULT_DAYS;
        }
        //Log::add(date('Y-m-d', strtotime("-$days days")));

		$eventList = Event::getAll(0,ServerSettings::MAX_QUERY, date('Y-m-d', strtotime("-$days days")));
		return  array( 'Events' =>DataObject::objectListToArrayList($eventList));
	}


    //=======================getEvents===================================
    // REST CALL: URL\getEvents
    // Description : Returns all events in the System
    //
    //===================================================================
    function action_getRules(){
        $ruleList = Ruleset::getAll(0,ServerSettings::MAX_QUERY);
        foreach ( $ruleList as $localRule){
            $localRule->ruleSetFile = 'JSON FILE';
        }
        return  array( 'Rulesets' =>DataObject::objectListToArrayList($ruleList));
    }


    //=======================getRulesForGame===================================
    // REST CALL: URL\getRulesForGame?gameId={Int}
    // Description : Gets the rule for the given game.
    //===================================================================
    function action_getRulesForGame(){
        $gameId = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
        if($gameId == -1 || Game::get($gameId) == null ){
            return  array('Action'=>'Failed');
        }
        $rule = Ruleset::getRulesForGame($gameId);
        if ($rule == null){
            return  array('Action'=>'Failed');
        }
        return array( 'Rule' => $rule->ruleSetFile);
    }


	//=======================createGame================================
	// REST CALL: URL\createGame?eventId={int}&gameName={String}
	// Description : Creates a game and four player slots.
	//
	//=======================================================================
	function action_createGame(){

		$eventId = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		$eventName = Request::get('gameName','',ServerSettings::PARAMS_LOCATION);
		// Check our events
		if( $eventId == -1 || $eventName == '' || Event::Get($eventId )==null){
			return array('Action'=>'Failed');
		}
		$newGame = new Game();
		$newGame->eventId = $eventId;
		$newGame->gameName = $eventName;
		$newGame->activePlayerId = NULL;
		$newGame->currentStatusId = GameState::LOADING; // Game not started
		$newGame->startTime = null;
		$newGame->endTime = null;
		$newGame->maxPlayers = ServerSettings::MAX_PLAYERS;
		$newGame->gameState ='{"Status": "Waiting"}';// This needs to change
		$newGame->checkSave();
		// create blank player slots
		for( $playerNumber =0; $playerNumber < ServerSettings::MAX_PLAYERS; $playerNumber++){
			$newUser = new User();
			$newUser->eventId = $eventId;
			$newUser->deviceId = '';
			$newUser->isGameMaster = false;
			$newUser->isFacilitator = false;
			$newUser->playerNumber = $playerNumber;
			$newUser->lastUpdate = null;
			$newUser->gameId = $newGame->id;
			$newUser->checkSave();
		}
		return  array('Action'=>'Success');
	}
	//=======================getGames===================================
	// REST CALL: URL\getGames
	// Description : Returns all games
	//
	//===================================================================
	function action_getGames(){
		$gamesList = Game::getAll(0,ServerSettings::MAX_QUERY);
		return  array( 'Games' =>DataObject::objectListToArrayList($gamesList));
	}
	//=======================getGamesInEvent===================================
	// REST CALL: URL\getGamesInEvent?eventId={int}
	// Description : Returns all games in a event
	//
	//===================================================================
	function action_getGamesInEvent(){
		$gamesList = Game::getAllGamesInEvent(0,ServerSettings::MAX_QUERY,Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION));
		return  array( 'Games' =>DataObject::objectListToArrayList($gamesList));
	}


	//=======================createGameMaster===================================
	// REST CALL: URL\createGameMaster?deviceId={String}&eventId={int}
	// Description : Joins/Rejoins a Game Master to an Event
	//
	//=======================================================================
	function action_createGameMaster(){
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$eventId = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		if($eventId == -1 || $deviceId ==''|| Event::get($eventId) == null){
			return  array('Action'=>'Failed');
		}
		$currentUsers = User::getGameMastersInEvent($eventId);
		foreach($currentUsers as $user){
			if($user->deviceId == $deviceId){
				$user->lastUpdate =  time();// time
				$user->checkSave();
				return array('Action'=>'Success');
			}
		}
		$newUser = new User();
		$newUser->eventId = $eventId;
		$newUser->deviceId = $deviceId;
		$newUser->isGameMaster = true;
		$newUser->isFacilitator = false;
		$newUser->playerNumber = -1;
		$newUser->lastUpdate = null;
		$newUser->gameId = null;
		$newUser->checkSave();
		return array('Action'=>'Success');
	}
		
	//=======================facilitatorJoin================================
	// REST CALL: URL\facilitatorJoin?deviceId={String}&gameId={int}
	// Description : Joins/Rejoins a Facilitator to a Game
	//
	//=======================================================================
	function action_facilitatorJoin(){
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$gameId = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$game = Game::Get($gameId);
		if($gameId == -1 || $deviceId ==''|| $game==null){
				
			return  array('Action'=>'Failed');
		}
		$currentUsers = User::getFacilitaorsInEvent($game->eventId);
		foreach($currentUsers as $user){
			// Join/re join the game
			if($user->deviceId == $deviceId){
				$user->gameId = $gameId;
				$user->lastUpdate = time();
				$user->checkSave();
				EventsModule::sendMessage($user->id ,$gameId,ServerMessages::PLAYER_JOINED, ServerMessageType::SERVER_MESSAGE);

				return array('Action'=>'Success');
			}
		}

		return array('Action'=>'Failed');
	}
	//=======================createFacilitator==============================
	// REST CALL: URL\FacilitatorJoin?deviceId={String}&eventId={int}
	// Description : Creates a Facilitator
	//
	//=======================================================================
	function action_createFacilitator(){
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$eventId = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		$event = Event::Get($eventId);

		if($eventId == -1 || $deviceId ==''|| $event==null){
			return  array('Action'=>'Failed');
		}
		$currentUsers = User::getFacilitaorsInEvent($eventId);
		foreach($currentUsers as $user){
			if($user->deviceId == $deviceId){
				$user->lastUpdate =  time();// time
				$user->checkSave();
				return array('Action'=>'Success');
			}
		}
		$newUser =  new User();
		$newUser->eventId = $eventId;
		$newUser->deviceId = $deviceId ;
		$newUser->isGameMaster = false;
		$newUser->isFacilitator = true;
		$newUser->playerNumber = -1;
		$newUser->lastUpdate = time();
		$newUser->gameId = NULL;
		$newUser->checkSave();
		return array('Action'=>'Success');
	}

    /****
     * REST CALL: URL\startPlayerCreation?deviceId={String}&eventId={int}
     * This function starts player creation!
     * @return json the success or failure message!
     */
    function action_startPlayerCreation(){
        $eventId   = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
        $deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
        if(  $eventId == -1 || $deviceId == ''|| Event::Get($eventId) == null ){
            return array('Action'=>'Failed');
        }
        $user =  User::getGameMaster($eventId,$deviceId);
        if($user == null) {
            return array('Action'=>'Failed');
        }
        $gamesList = Game::getAllGamesInEvent(0,ServerSettings::MAX_QUERY,$eventId);
        Game::lockGame();
        foreach($gamesList as $currentGame){
            if($currentGame->currentStatusId == GameState::LOADING){
                $currentGame->currentStatusId = GameState::WAITING_FOR_PLAYER_CREATION;
                $currentGame->checkSave();
                EventsModule::sendMessage($user->id ,$currentGame->id,ServerMessages::WAITING_FOR_PLAYER_CREATION,ServerMessageType::SERVER_MESSAGE);
            }
        }
        Game::unlockTables();
        return array('Action'=>'Success');
    }


	//=======================playerJoin=====================================
	// REST CALL: URL\playerJoin?deviceId={String}&gameId={int}
	// Description : Joins/Rejoins a Player to a Game
	//
	//=======================================================================
	function action_playerJoin(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		if(  $gameId == -1 || $deviceId == ''|| Game::Get($gameId) == null){
			return array('Action'=>'Failed');
		}
		$game = Game::get($gameId);
		// Incase to players try to join the same slot lock the game!
		$event =  Event::get($game->eventId);
		User::lockTable();
		$players = User::getPlayersInGame($gameId);
		//If its still in the joining phase up to Max
		if( $game->currentStatusId == GameState::LOADING ){
			$playerCount = ServerSettings::MAX_PLAYERS;
		}
		else{
			// The game has been started only the max that where in the orginal game can play
			$playerCount =  $game->maxPlayers;
		}
		$newDate = time();
		
		if( $game->currentStatusId != GameState::LOADING ){
			for($playerNumber = 0; $playerNumber < $playerCount; $playerNumber++){
				if($players[$playerNumber]->deviceId === $deviceId){
					// Just Update the player timestamp and send message
					$players[$playerNumber]->lastUpdate =  $newDate;
					$players[$playerNumber]->deviceId  = $deviceId;
					$players[$playerNumber]->save();
					User::unlockTables();
					EventsModule::sendMessage($players[$playerNumber]->id ,$gameId, ServerMessages::PLAYER_JOINED,ServerMessageType::SERVER_MESSAGE);
					return array('Action'=>'Success');
				}
			}
		}
		// Check for Missing Slots or Disconnected players add the player/*		
		for($playerNumber = 0; $playerNumber <$playerCount; $playerNumber++){
			$players[$playerNumber]->reload();
			if($players[$playerNumber]->deviceId=='' ||$players[$playerNumber]->deviceId == null| (($newDate - $players[$playerNumber]->lastUpdate)   > ServerSettings::PLAYER_TIMEOUT&& $event->isAsync == false))  {
				$players[$playerNumber]->lastUpdate =  $newDate;
				$players[$playerNumber]->deviceId  = $deviceId;
				$players[$playerNumber]->save();
				User::unlockTables();
				EventsModule::sendMessage($players[$playerNumber]->id ,$gameId, ServerMessages::PLAYER_JOINED,ServerMessageType::SERVER_MESSAGE);
				return array( 'Action'=>'Success');
			}
		}
		User::unlockTables();
		return  array('Action'=>'Failed');
	}


	//=======================getPlayers=====================================
	// REST CALL: URL\getPlayers?deviceId={String}&gameId={int}
	// Description : Gets a List of active players
	//
	//=======================================================================
	function action_getPlayers(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$game =  Game::get($gameId);
		if($game == null || $gameId ==  -1){
			return array("Players" => array());
		}
		if(Event::get($game->eventId)->isAsync){
			$players = User::getPlayersInGame($gameId);
		}
		else{
			$players = User::getActivePlayers($gameId,ServerSettings::PLAYER_TIMEOUT);
		}
		return array("Players" =>  DataObject::objectListToArrayList($players));
	}

	//=======================getFacilitators=====================================
	// REST CALL: URL\getFacilitators?gameId={int}
	// Description : Gets a List of active Facilitaors
	//
	//==========================================================================
	function action_getFacilitators(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$game =  Game::get($gameId);
		if($game == null || $gameId ==  -1){
			return array("Facilitaors" => array());
		};
		if(Event::get($game->eventId)->isAsync){
			$players = User::getFacilitaorsInGame($gameId);
		}
		else{
			$players = User::getActiveFacilitaors($gameId,ServerSettings::PLAYER_TIMEOUT);
		}
		return array("Facilitators" =>  DataObject::objectListToArrayList($players));
	}

	//==================Private API =============================================
	// Only Devices that are in the DB are allowed to call these functions
	// Anyother calls will return as invalid!
	//===========================================================================



	//============================startGame=====================================
	// REST CALL: URL\startGame?deviceId={String}&gameId={int}
	// Description : starts the given game if possible
	//
	//==========================================================================
	function action_startGame(){

		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
        $gameState = Request::get('gameState','',ServerSettings::PARAMS_LOCATION);
		$game  = Game::get($gameId);
		if($gameId == -1 || $game== null || $deviceId =='' ){
			return array("Action" => "Failed");
		}
		if(Event::get($game->eventId)->isAsync){
			$players = User::getPlayersInGame($gameId);
			$fac = User::getFacilitaorsInGame($gameId);
		}
		else{
			$players = User::getActivePlayers($gameId,ServerSettings::PLAYER_TIMEOUT);
			$fac = User::getActiveFacilitaors($gameId,ServerSettings::PLAYER_TIMEOUT);
		}
		$user =  EventsModule::getUser($deviceId,$gameId,$game->eventId);
        // Loading cant start games now!
        if( $game->currentStatusId == GameState::LOADING){
            return array("Action" => "Failed");
        }
		if($user  != null && count($fac) >= 1 &&  count($players) >= ServerSettings::MIN_PLAYERS &&$game->currentStatusId != GameState::RUNNING ){
			Game::lockGame();
			// This is the first time the game is 'started' save the players
			if($game->currentStatusId == GameState::WAITING_FOR_PLAYER_CREATION){
				$game->maxPlayers = count($players);
				// Player 1 gets the first turn!
				$game->startTime = time();
				//$newGame->activePlayerId = $players[0]->id;
                // The game state is required for starting the game!
                $changeTurnID = EventsModule::sendMessage($user->id,$game->id, ServerMessages::RESUME, ServerMessageType::SERVER_MESSAGE);
                $game->currentStatusId = GameState::RUNNING;
                $game->gameState = str_replace("CHANGETURNID", $changeTurnID, $gameState);
                $game->checkSave();
                Game::unlockTables();
			}
			else {
                $game->currentStatusId = GameState::RUNNING;
                $game->checkSave();
                Game::unlockTables();
                EventsModule::sendMessage($user->id ,$gameId, ServerMessages::RESUME,ServerMessageType::SERVER_MESSAGE);
            }
			return array("Action" => "Success");
		}
		Game::unlockTables();
		return array("Action" => "Failed");
	}
	//============================resumeAllGames=================================
	// REST CALL: URL\resumeAllGames?deviceId={String}&eventId={int}
	// Description : starts the given game if possible
	//
	//==========================================================================
	function action_resumeAllGames(){
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$eventId   = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		if($eventId == -1 || Event::Get($eventId) == null  || $deviceId =='' ){
			 
			return array("Action" => "Failed");
		}
		$user =  EventsModule::getUser($deviceId,-1,$eventId);
		if($user == null ){
			 
			return array("Action" => "Failed");
		}
		$event = Event::get($eventId);
		$gamesList = Game::getAllGamesInEvent(0,ServerSettings::MAX_QUERY, $eventId );
		foreach ($gamesList as $game){
			if($event->isAsync){
				$players = User::getPlayersInGame($game->id);
				$fac = User::getFacilitaorsInGame($game->id);
			}
			else{
				$players = User::getActivePlayers($game->id,ServerSettings::PLAYER_TIMEOUT);
				$fac = User::getActiveFacilitaors($game->id,ServerSettings::PLAYER_TIMEOUT);
			}
			if(count($fac) >= 1 &&  count($players) >= ServerSettings::MIN_PLAYERS && $game->currentStatusId == GameState::PAUSED ){
				$game->currentStatusId = GameState::RUNNING;
				$game->checkSave();
				EventsModule::sendMessage($user->id ,$game->id,ServerMessages::RESUME,ServerMessageType::SERVER_MESSAGE);
			}
		}
		return array("Action" => "Success");
	}

	//============================startGame=====================================
	// REST CALL: URL\startGame?deviceId={String}&gameId={int}
	// Description : starts the given game if possible
	//
	//==========================================================================
	function action_pauseGame(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$game = Game::get($gameId);
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		if($gameId == -1 || $game== null || $deviceId =='' || $user == null ){
			 
			return array("Action" => "Failed");
		}
		if($game->currentStatusId ==  GameState::RUNNING){
			$game->currentStatusId = GameState::PAUSED;
			$game->checkSave();
			EventsModule::sendMessage($user->id ,$gameId,ServerMessages::PAUSED,ServerMessageType::SERVER_MESSAGE);
			return array("Action" => "Success");
		}

		return array("Action" => "Failed");
	}

	//============================pauseAllGames=================================
	// REST CALL: URL\startGame?deviceId={String}&eventId={int}
	// Description : starts the given game if possible
	//
	//==========================================================================
	function action_pauseGameAll(){
		$eventId   = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
			
		$event = Event::get($eventId);
		$user = EventsModule::getUser($deviceId,-1,$eventId);
			
		if( $eventId == -1|| $event == null || $deviceId =='' || $user == null ){
			return array("Action" => "Failed");
		}
		$gamesList = Game::getAllGamesInEvent(0,ServerSettings::MAX_QUERY, $eventId );
		foreach ($gamesList as $game){
			if($game->currentStatusId ==  GameState::RUNNING){
				$game->currentStatusId = GameState::PAUSED;
				$game->checkSave();
				EventsModule::sendMessage($user->id ,$game->id,ServerMessages::PAUSED,ServerMessageType::SERVER_MESSAGE);
			}
		}
		return array("Action" => "Success");
	}


	//============================pushGameUpdate=================================
	// REST CALL: URL\pushGameUpdate?deviceId={String}&gameId={int}&messageType={int}&updateData={String}
	// Description : Pushes the current game state and any events to the queue
	//
	//==========================================================================
	function action_pushGameUpdate(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$messageType = Request::get('messageType',-1,ServerSettings::PARAMS_LOCATION);
		$game = Game::get($gameId);
		$gameEventType =  GameEventType::get($messageType);
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		$updateData = Request::get('updateData','',ServerSettings::PARAMS_LOCATION);
		if($updateData== ''||$gameId == -1 || $game== null || $deviceId =='' || $user == null || $messageType == -1 || $gameEventType == null){
				
			return array("Action" => "Failed");
		}
		if($user != null){
			EventsModule::sendMessage($user->id,$gameId,$updateData,$messageType);
			$user->lastUpdate = time();
			$user->checkSave();
				
			return array('Action'=>'Success');
		}

		return array('Action'=>'Failed');
	}
	//============================pushGameUpdateAll=================================
	// REST CALL: URL\pushGameUpdate?deviceId={String}&eventId={int}&messageType={int}&updateData={String}
	// Description : Pushes the current game state and any events to the queue
	//
	//==========================================================================
	function action_pushGameUpdateAll(){
		$eventId   = Request::getInt('eventId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$messageType = Request::get('messageType',-1,ServerSettings::PARAMS_LOCATION);
		$gameEventType =  GameEventType::get($messageType);
		$user = EventsModule::getUser($deviceId,-1,$eventId);
		$event = Event::get($eventId);
		$updateData = Request::get('updateData','',ServerSettings::PARAMS_LOCATION);
		if( $eventId == -1 || $updateData== ''|| $event== null || $deviceId =='' || $user == null || $messageType == -1 || $gameEventType == null){
			return array("Action" => "Failed");
		}
		if($user != null){
			$gamesList = Game::getAllGamesInEvent(0,ServerSettings::MAX_QUERY, $eventId );
			foreach($gamesList as $game){
				EventsModule::sendMessage($user->id,$game->id,$updateData,$messageType);
			}
			$user->lastUpdate = time();
			$user->checkSave();
			return array('Action'=>'Success');
		}
		return array('Action'=>'Failed');
	}



	//============================getUpdate====================================
	// REST CALL: URL\getUpdate?deviceId={String}&gameId={int}&lastGameEventId={int}
	// Description : Pushes any events to the queue also will add check heat beat!
	//
	//==========================================================================
	function action_getUpdate(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$lastGameEventId = Request::getInt('lastGameEventId',-1,ServerSettings::PARAMS_LOCATION);
		// Must send an int!
		$game  = Game::get($gameId);
		if($lastGameEventId == -1 || $gameId ==  -1 || $game== null ){
				
			return array('Action'=>'Failed');
		}
			
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		if($user == null){
				 
			return array('Action'=>'Failed');
		}
		$user->lastUpdate = date('Y-m-d H:i:s',time());//time();
		$user->checkSave();
		// Check heat beat
		// Always check the game state before saving this way we prvent mutiple sync messages!
		if(! Event::get($game->eventId)->isAsync ){
			$players = User::getActivePlayers($gameId,ServerSettings::PLAYER_TIMEOUT);
			$fac = User::getActiveFacilitaors($gameId,ServerSettings::PLAYER_TIMEOUT);
			if( count($fac) < 1  &&	$game->currentStatusId  == GameState::RUNNING){
				$game->currentStatusId  = GameState::WAITING_FOR_FACILITATOR ;
				$game->checkSave();
				EventsModule::sendMessage($user->id,$gameId, ServerMessages::WAITING_FOR_FACILITATOR,ServerMessageType::SERVER_MESSAGE);
			}
			else if(count($players ) <$game->maxPlayers &&	$game->currentStatusId  == GameState::RUNNING){
				EventsModule::sendMessage($user->id,$gameId, ServerMessages::WAITING_FOR_PLAYER,ServerMessageType::SERVER_MESSAGE);
				$game->currentStatusId  = GameState:: WAITING_FOR_PLAYER ;
				$game->checkSave();
			}
			else if ((count($players ) >=$game->maxPlayers &&  count($fac ) >=1) &&( $game->currentStatusId  == GameState::WAITING_FOR_PLAYER || $game->currentStatusId  == GameState::WAITING_FOR_FACILITATOR )){
				EventsModule::sendMessage($user->id,$gameId, ServerMessages::RESUME, ServerMessageType::SERVER_MESSAGE);
				$game->currentStatusId  = GameState::RUNNING ;
				$game->checkSave();
			}
		}else{
			// since they can rejoin games this must be checked
			$fac = User::getFacilitaorsInGame($gameId);
			if( count($fac) < 1  &&	$game->currentStatusId  == GameState::RUNNING){
				$game->currentStatusId  = GameState::WAITING_FOR_FACILITATOR ;
				$game->checkSave();
				EventsModule::sendMessage($user->id,$gameId, ServerMessages::WAITING_FOR_FACILITATOR,ServerMessageType::SERVER_MESSAGE);
			}else if($game->currentStatusId  == GameState::WAITING_FOR_FACILITATOR) {
				EventsModule::sendMessage($user->id,$gameId, ServerMessages::RESUME,ServerMessageType::SERVER_MESSAGE);
				$game->currentStatusId  = GameState::RUNNING ;
				$game->checkSave();
			}
		}
		//If the requester is a game master then check the game Id
		$GameEventsLogs = GameEvent::getNewEvents($lastGameEventId,$gameId);
		return  array( 'GameEvents' =>DataObject::objectListToArrayList($GameEventsLogs));
	}

	//============================getGamestate===================================
	// REST CALL:  URL\getGameState?deviceId={String}&gameId={int}&lastGameEventId={int}
	// Description : Gets the Current Gamestate
	//
	//==========================================================================
	function action_getGameState(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		// Must send an int!
		$game  = Game::get($gameId);
		if( $gameId ==  -1 || $game== null ){
			return array('Action'=>'Failed');
		}
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		if($user != null){
			$user->lastUpdate = time();
			$user->checkSave();
			return  array( 'GameState' =>$game->gameState);
		}
		else{
			return array('Action'=>'Failed');
		}
	}

	//============================changeTurn==================================
	// REST CALL:  URL\getGameState?deviceId={String}&gameId={int},GameState={String}
	// Description : sets the current Gamestate
	//
	//==========================================================================
	function action_changeTurn(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		$gameState = Request::get('gameState','',ServerSettings::PARAMS_LOCATION);
		// Must send an int!
		$game = Game::get($gameId);
		$event = Event::get($game->eventId);
		// Only allow change turn if the game is running!
		if( $gameId ==  -1 || $game == null  || $game->currentStatusId != GameState::RUNNING){
			return array('Action'=>'Failed');
		}
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		if ($user != null) {
			$changeTurnID = EventsModule::sendMessage($user->id,$game->id, ServerMessages::CHANGE_TURN, ServerMessageType::SERVER_MESSAGE);
			Game::lockGame();
			$game->gameState = str_replace("CHANGETURNID", $changeTurnID, $gameState);
			$game->checkSave();
			Game::unlockTables();
			
			return array('Action'=>'Success');
		}
		Game::unlockTables();
		return array('Action'=>'Failed');
	}



	//============================endGame====================================
	// REST CALL:  URL\endGame?deviceId={String}&gameId={int}
	// Description : sets the current Gamestate
	//
	//==========================================================================
	function action_endGame(){
		$gameId   = Request::getInt('gameId',-1,ServerSettings::PARAMS_LOCATION);
		$deviceId = Request::get('deviceId','',ServerSettings::PARAMS_LOCATION);
		// Must send an int!
		$game = Game::get($gameId);
		$event =  Event::get($game->eventId);
		// Only allow change turn if the game is running!
		if( $gameId ==  -1 || $game== null  || $game->currentStatusId != GameState::RUNNING){
			return array('Action'=>'Failed');
		}
		$user = EventsModule::getUser($deviceId,$gameId,$game->eventId);
		if($user != null){
			$game->endTime = time();
			$game->currentStatusId = GameState::GAME_OVER;
			$game->checkSave();
			EventsModule::sendMessage($user->id,$game->id,ServerMessages::GAME_OVER,ServerMessageType::SERVER_MESSAGE);
			return array('Action'=>'Success');
		}
		return array('Action'=>'Failed');
	}

	//====================== Internal Methods==================================
	// These methods are for internal calls only
	//=========================================================================
	/**
	*
	* Saves a message GameEvent Queue with the current timestamp
	* @param string $userId
	* @param int $gameId
	* @param string $Json
	* @param int $eventType
	*/
	static function sendMessage($userId,$gameId,$Json,$eventType){
		$newGameEvent = new GameEvent();
		$newGameEvent->eventTypeId = $eventType;
		$newGameEvent->gameId =  $gameId  ;
		$newGameEvent->userId = $userId;
		$newGameEvent->data = $Json;
		$newGameEvent->date = time();
		$newGameEvent->checkSave();
		return $newGameEvent->getId();
	}
	/**
	 *
	 * Gets the user based on the device, game and event
	 * @param string $deviceId
	 * @param int $gameId
	 * @param int $eventId
	 */
	static function getUser($deviceId,$gameId,$eventId){
		$gameMaster = User::getGameMaster($eventId,$deviceId);
		if( $gameMaster !=null ){
			return $gameMaster;
		}
		else{

			$user =  User::getPlayer($deviceId,$gameId);
			if ($user != null ){
				return $user;
			}
			return  User::getFacilitator($deviceId,$gameId);
		}
	}


	//=========================================================================


	//======================Required Methods===================================
	// These method are required for mudpuppy to work
	//=========================================================================
	public function getRequiredPermissions($method, $input) {
		// TODO: add permissions as needed
		return array();
	}
	//==========================================================================

}


?>