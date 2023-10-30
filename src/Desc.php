<?php

namespace MyApp;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Desc implements MessageComponentInterface{
	public $clients;
	public $init;

	public function __construct()
	{
		$this->clients = new \SplObjectStorage;
		echo $this->handleLog("WS server started.\r\n\r\n");
	}

	public function onOpen(ConnectionInterface $conn): void
	{
		$this->clients->attach($conn);

		echo $this->handleLog("New connection ".$this->getIdTag($conn)."\r\n");

		$init = new Init();
		if($init->chargeStationConnect($this->getIdTag($conn))) {
			echo $this->handleLog($this->getIdTag($conn)." - "."The charging station has been authorized\r\n\r\n");
			$this->init = $init;

			//Sometimes it happens that several open WebSocket channels are created for one charging station, our task is to delete old connections

			//If the number of connections is greater than or equal to 1
			if(count($this->clients) >= 1) {
				//Create a new OWN array with a list of connections
				$sp = 0;
				foreach($this->clients as $client) {
					$allconn[$client->resourceId] = $this->getIdTag($client);
					$sp++;
				}

				/// Count the number of all values in the array, if the values are greater than 1, that is, a match that we need to remove
				$result = array_count_values($allconn);

				//Start the loop :)
				foreach($result as $key => $value) {
					//If the current connection is equal to the one in the loop and has a match (greater than 1), then draw attention to this
					if($key == $this->getIdTag($conn) && $value > 1) {
						echo 'Worth paying attention here '.$this->getIdTag($conn).PHP_EOL.PHP_EOL;
						// Loop through the previously created owl array with a list of connections
						foreach($allconn as $keys => $val) {
							if($val == $this->getIdTag($conn)) //If there are matches from the array with the list of connections with the current connection
							{
								if($conn->resourceId != $keys) {
									foreach($this->clients as $variable) {
										if($val == $this->getIdTag($variable)) {
											echo $val.' going to be removed '.$keys.PHP_EOL.PHP_EOL;
											$this->onClose($variable).' allconn '.PHP_EOL.PHP_EOL;
											break;
										}
									}
								}
							}

						}
					}

				}


			}

		} else {
			echo $this->handleLog($this->getIdTag($conn)." - "."Charging station NOT authorized \r\n\r\n");
			$this->onClose($conn);
		}
	}


	public function onMessage(ConnectionInterface $from, $msg): void
	{
		foreach($this->clients as $client) {
			if($from === $client) {
				if(is_array(json_decode($msg))) {
					$init = new Init();
					echo $this->handleLog('FROM CP - '.$this->getIdTag($from).' - '.date('H:i:s').' '.$msg.PHP_EOL.PHP_EOL); //We write the log
					$respon = $init->processChargePointMessage(json_decode($msg), $this->getIdTag($from)); //We process the received command from the charging station
					if($respon != NULL) //If the method is not defined on the server, we work it out and write the command
					{
						$client->send($respon);
						$init->up_command($respon, $this->getIdTag($from)); //Write the log from the station
					}
				} //END if is_array
			}//END $from === $client
		}// END foreach
	}

	public function onMessageTimer(ConnectionInterface $from, $msg)
	{
		foreach($this->clients as $client) {
			if($from === $client) {
				$client->send($msg);
			}
		}
	}

	public function onClose(ConnectionInterface $conn)
	{
		$this->clients->detach($conn);
		echo $this->handleLog("Connection closed ".$this->getIdTag($conn)."\r\n\r\n");
	}

	public function onError(ConnectionInterface $conn, \Exception $e)
	{
		echo $this->handleLog("Error: {$e->getMessage()}\r\n");
		$conn->close();
	}

	// Get the station ID
	public function getIdTag($conn)
	{
		$request = $conn->httpRequest;
		$pieces = explode("/", $request->getUri()->getPath());
		$reversed = array_reverse($pieces);

		return $reversed[0];
	}
	// Get the station ID

	//We write logs
	public function handleLog($record)
	{
		//$filename = __DIR__.'/server.log';
		//file_put_contents($filename, $record, FILE_APPEND | LOCK_EX);
		return $record;
	}
	//We write logs

}
