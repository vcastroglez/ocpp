<?php
//Forgive me my friend, but I can't make part of this file publicly available.
//This is a part of my life on which I spent a lot of time.
//If you want to get the source file or my advice, write to me in direct https://www.instagram.com/gennadiy.gnezdilov/

namespace MyApp;

include('config/define.php');

class Init{
	private $db;
	private $idTag;
	private $name;
	private $connectorIdS = 0;
	private $type;
	private $user_id = 1;

	function __construct()
	{
		$this->db = new MySQL(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);
	}

	//Check if there are permissions for the charging station to connect to our server
	public function SelectConected($idTag)
	{
		self::out($idTag);
		$this->db->query("INSERT INTO connections SET uuid = '$idTag'");
		return true;
	}
	//Check if there are permissions for the charging station to connect to our server

	//Ping the my_set_command database and if there is a command, send the command to the charging station, then delete the command and the database
	public function SetCommand($idTag){}
	//Ping the my_set_command database and if there is a command, send the command to the charging station, then delete the command and the database

	//We write to the temporary base which connector was launched
	public function UpConnectorCommand($idTag, $connector_id = 0){}
	//We write to the temporary base which connector was launched

	//Write to the temporary base who launched the command
	public function UpUserCommand($idTag, $user_id = 0){}
	//Write to the temporary base who launched the command

	//Get the user id who last ran the command on the current station
	public function UserID($idTag){}
	//Get the user id who last ran the command on the current station

	//Get the connector id who last ran the command on the current station
	public function ConnectorSID($idTag){}
	//Get the connector id who last ran the command on the current station

	//Write to the database, the history of commands sent manually
	public function up_command($data, $idTag)
	{
		self::out("---------------------------------------------");
		self::out($data);
		self::out($idTag);
		self::out("---------------------------------------------");
	}

	//Write to the database, the history of commands sent manually

	private array $allow_msg = [
		'Authorize',
		'BootNotification',
		'Heartbeat',
		'StatusNotification',
		'StartTransaction',
		'StopTransaction',
		'MeterValues'
	];

	//We receive some data from the station and process it through the switch - we answer
	public function Status($data, $idTag = '')
	{
		$id = $data[1];
		$action = $data[2];
		if(!in_array($action, $this->allow_msg))
			return null;

		if($action == 'BootNotification') {
			$this->handleMeterValues($data, $idTag);
			return $this->sendSuccessfulBootResponse($id);
		} else if($action == 'StatusNotification') {
			$this->handleMeterValues($data, $idTag);
			return $this->sendSuccessfulSstatusNotificationResponse($id);
		} else if($action == 'Heartbeat') {
			return $this->sendSuccessfulSstatusNotificationResponse($id);
		} else if($action == 'StartTransaction') {
			$this->handleMeterValues($data, $idTag);
			return $this->sendSuccessfulStartTransaction($id);
		} else {
			$this->handleMeterValues($data, $idTag);
		}

		if($action == 'StopTransaction' || $action == 'MeterValues') {
			$this->handleMeterValues($data, $idTag);
		}
		return '[3,"'.$id.'",{"idTagInfo":{"status":"Accepted"}}]';
	}

	//We receive some data from the station and process it through the switch - we answer

	public function StartTransactionStatus($idTag, $data){}

	public function AuthorizeStatus($idTag, $data)
	{
		return true;
	}

	//Write the firmware version
	public function SetBootNotification($data){}
	//Write the firmware version

	//Record the heartbeat time in the Heartbeat method
	public function SetHeartbeat($idTag, $data){}
	//Record the heartbeat time in the Heartbeat method

	//Write the status of the stations in the StatusNotification method
	public function SetStatus($data){}
	//Write the status of the stations in the StatusNotification method

	//Write a fake 0, MeterValues
	public function SetFalseMeterValues($idTag, $user_id, $rand){}

	///Updating connector information
	public function connectorId($data){}
	//Updating connector information

	//Get the user id who started the transaction
	public function UserTransactionId($transaction = 0){}
	//Get the user id who started the transaction

	//Determinations through which algorithm to write off money
	public function MeterStartZero($idTag, $transactionId){}
	//Determinations through which algorithm to write off money

	//Write data from counters
	public function MeterValues($idTag, $data)
	{
		self::out($data);
	}

	//Write data from counters

	public function NotStandardAlgorithm($data, $idTag, $zero){}

	public function StandardAlgorithm($data, $idTag){}

	//Ticketing
	public function SetMoney($parent_id){}

	// Special price for some users
	public function SpecialMoney($idTag, $connectorId, $user_id){}
	// Special price for some users

	//What type is this number
	public function SetType($type){}
	//What type is this number

	//Search for the Energy.Active.Import.Register value in the array
	public function searchForId($id, $array){}

	public static function out($value)
	{
		if(!is_string($value)) {
			$value = json_encode($value);
		}

		echo "OUT -> ".$value.PHP_EOL;
	}

	private function handleMeterValues($data, mixed $idTag): void
	{
		file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.$idTag.'.log', json_encode($data).PHP_EOL, FILE_APPEND);
	}

	private function sendSuccessfulBootResponse(mixed $id): string
	{
		$status = "Accepted";
		$currentTime = date('Y-m-d\TH:i:s\Z');
		$interval = 10;
		return "[3,\"$id\",{\"currentTime\":\"$currentTime\",\"status\":\"$status\",\"interval\":$interval}]";
		return '[3,"'.$id.'",{"idTagInfo":{"currentTime":"","status":"Accepted"}}]';
	}

	private function sendSuccessfulSstatusNotificationResponse(mixed $id): string
	{
		$status = "Accepted";
		$currentTime = date('Y-m-d\TH:i:s\Z');
		return "[3,\"$id\",{\"currentTime\":\"$currentTime\",\"status\":\"$status\"}]";
	}

	private function sendSuccessfulStartTransaction(mixed $id): string
	{
		$last_id = file_get_contents(__DIR__.'/log/last.log') ?? 0;
		$transaction_uid = intval($last_id)+1;
		file_put_contents(__DIR__.'/log/last.log',$transaction_uid);
		return '[3,"'.$id.'",{"idTagInfo":{"currentTime":"","status":"Accepted"},"transactionId":'.$transaction_uid.'}]';
	}

}
