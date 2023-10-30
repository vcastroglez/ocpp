<?php
//Ratchet Chat WS server
use MyApp\Desc;
use MyApp\Init;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require 'vendor/autoload.php';
require_once 'src/config/define.php';
//Initializing the WebSocket Server
$socket = new Desc();
$server = IoServer::factory(new HttpServer(new WsServer($socket)), env('WS_PORT', 5000));
//Initializing the WebSocket Server

//When the WebSocket Server connection is active, start a timer to check and send commands to the stations
$server->loop->addPeriodicTimer(env('WS_INTERVAL', 5), function() use ($socket){
	foreach($socket->clients as $client) {
		$init = new Init();
		$send = $init->SetCommand($socket->getIdTag($client)); //Checking if the commands are from the user
		if($send) {
			if($send['idTag'] === $socket->getIdTag($client)) {
				$init->UpUserCommand($socket->getIdTag($client), $send['user_id']); //If the command exists, write down the user id
				echo 'CS - user_id - '.$send['user_id'].' - '.$socket->getIdTag($client).' '.date('H:i:s').' '.$send['text'].PHP_EOL.PHP_EOL; //We write the log
				$client->send($send['text']); //We send a command to the required charging station
				$init->up_command($send['text'], $socket->getIdTag($client)); //Writing a log to the SQL database
			}
		}
	}
});
//When the WebSocket Server connection is active, start a timer to check and send commands to the stations

//Start WebSocket Server
$server->run();
//Start WebSocket Server
