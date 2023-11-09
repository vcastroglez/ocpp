<?php
//Forgive me my friend, but I can't make part of this file publicly available.
//This is a part of my life on which I spent a lot of time.
//If you want to get the source file or my advice, write to me in direct https://www.instagram.com/gennadiy.gnezdilov/

namespace MyApp;

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
	public function chargeStationConnect($id_tag): bool
	{
		if(empty($id_tag) || $id_tag == 'robots.txt' || $id_tag == 'sitemap.xml') {
			echo file_get_contents(__DIR__.'/robots.txt');
			return false;
		}

		self::out($id_tag);
		$charge_point = $this->getChargePoint($id_tag);
		if(!$charge_point) {
			$this->db->query("INSERT IGNORE INTO charge_points SET uuid = '$id_tag', created_at = NOW(), updated_at = NOW()");
		}
		return true;
	}
	//Check if there are permissions for the charging station to connect to our server

	//Ping the my_set_command database and if there is a command, send the command to the charging station, then delete the command and the database
	public function SetCommand($idTag)
	{
		$command = $this->getFirstCommand($idTag);
		if(empty($command)) {
			return false;
		}
		$uuid = md5(rand(0, 999999999));
		$payload = json_decode($command['payload'], true);
		$type = $command['message_type'] ?? 2;
		$action = $payload['action'];
		$text = json_encode($payload['text']);
		$this->db->query("DELETE FROM server_msg_queues WHERE id = {$command['id']}");
		return [
			'user_id' => $command['user_id'],
			'idTag'   => $idTag,
			'text'    => '['.$type.',"'.$uuid.'", "'.$action.'", '.$text.']'
		];
	}
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
	public function processChargePointMessage($data, $id_tag = ''): ?string
	{
		$id = $data[1];
		$action = $data[2];
		if(!in_array($action, $this->allow_msg))
			return null;

		$message_type = $this->getMessageType($action);
		if(empty($message_type['id'])) {
			$this->db->query("INSERT INTO message_types SET type = '$action', updated_at = NOW(), created_at = NOW()");
			$message_type = $this->getMessageType($action);
		}
		$charge_point = $this->getChargePoint($id_tag);
		$payload = $data[3] ?? null;
		$this->saveNewMessage($charge_point['id'], $message_type['id'], $payload);

		if($action == 'BootNotification') {
			return $this->sendSuccessfulBootResponse($id);
		} else if($action == 'StatusNotification') {
			return $this->sendSuccessfulSstatusNotificationResponse($id);
		} else if($action == 'Heartbeat') {
			return $this->sendSuccessfulSstatusNotificationResponse($id);
		} else if($action == 'StartTransaction') {
			$this->handleMeterValues($id_tag, $data);
			return $this->handleStartTransaction($id, $id_tag, $data);
		} else if($action == 'StopTransaction') {
			$this->handleMeterValues($id_tag, $data);
			$this->saveTransactionEnd($id_tag, $data);
		} else {
			$this->handleMeterValues($id_tag, $data);
		}


		return '[3,"'.$id.'",{"idTagInfo":{"status":"Accepted"}}]';
	}

	//We receive some data from the station and process it through the switch - we answer

	public function StartTransactionStatus($idTag, $data){}

	public function AuthorizeStatus($idTag, $data)
	{
		echo "Authorize".PHP_EOL;
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
		$encoded = $value;
		if(!is_string($value)) {
			$encoded = json_encode($value);
		}

		echo "OUT -> ".gettype($value).': '.$encoded.PHP_EOL;
	}

	private function handleMeterValues(string $idTag, $data): void
	{
		$charge_point = $this->getChargePoint($idTag);
		$payload = $data[3];
		$meter_value = $payload->meterStop ?? $payload->meterStart ?? null;
		if(is_null($meter_value)) {
			return;
		}

		$this->updateLastMeterValue($charge_point['id'], $meter_value);
	}

	private function sendSuccessfulBootResponse(mixed $id): string
	{
		$status = "Accepted";
		$currentTime = date('Y-m-d\TH:i:s\Z');
		$interval = 10;
		return "[3,\"$id\",{\"currentTime\":\"$currentTime\",\"status\":\"$status\",\"interval\":$interval}]";
	}

	private function sendSuccessfulSstatusNotificationResponse(mixed $id): string
	{
		$status = "Accepted";
		$currentTime = date('Y-m-d\TH:i:s\Z');
		return "[3,\"$id\",{\"currentTime\":\"$currentTime\",\"status\":\"$status\"}]";
	}

	private function handleStartTransaction(mixed $id, string $id_tag, array $payload): string
	{
		$charge_point = $this->getChargePoint($id_tag);
		$transaction_incremental = $charge_point['last_transaction_id'] + 1;
		$transaction_uid = $id_tag.'-'.$transaction_incremental;
		$this->updateLastTransaction($charge_point['id'], $transaction_incremental);
		$this->saveTransactionStart($charge_point['id'], $transaction_uid, $payload[3]->meterStart);
		return '[3,"'.$id.'",{"idTagInfo":{"currentTime":"","status":"Accepted"},"transactionId":"'.$transaction_uid.'"}]';
	}

	private function getMessageType(mixed $action): ?array
	{
		return $this->db->query("SELECT id FROM message_types WHERE type='$action'")[0] ?? null;
	}

	private function getChargePoint($id_tag): ?array
	{
		return $this->db->query("SELECT * FROM charge_points WHERE uuid = '$id_tag'")[0] ?? null;
	}

	private function saveNewMessage(mixed $charge_point_id, mixed $message_id, mixed $payload): void
	{
		$payload = json_encode($payload);
		@$this->db->query("INSERT INTO messages SET id_charge_point = {$charge_point_id}, id_message_type = {$message_id}, payload = '$payload', updated_at = NOW(), created_at = NOW()");
	}

	private function updateLastTransaction(int $id, int $transaction_uid): void
	{
		@$this->db->query("UPDATE charge_points SET last_transaction_id = $transaction_uid, updated_at = NOW() WHERE id = $id");
	}

	private function updateLastMeterValue(mixed $id, $meter_value)
	{
		@$this->db->query("UPDATE charge_points SET last_meter_value = $meter_value, updated_at = NOW() WHERE id = $id");
	}

	private function saveTransactionStart(int $id_charge_point, string $transaction_uid, int $meter_start)
	{
		@$this->db->query("INSERT INTO transactions SET id_charge_point = {$id_charge_point}, transaction_uuid = '$transaction_uid', meter_start = $meter_start, updated_at = NOW(), created_at = NOW()");
	}

	private function saveTransactionEnd(mixed $id_tag, $data)
	{
		$charge_point = $this->getChargePoint($id_tag);
		$stop_transaction = $data[3]->meterStop;
		$transaction_id = $data[3]->transactionId;
		//		$reason = $data[3]->reason;//todo
		@$this->db->query("UPDATE transactions SET meter_stop = $stop_transaction WHERE transaction_uuid = '$transaction_id' AND id_charge_point = {$charge_point['id']}");
	}

	private function getFirstCommand($idTag)
	{
		$charge_point = $this->getChargePoint($idTag);
		if(empty($charge_point)) {
			self::out("[WARNING] No charpoint found with idTag: $idTag");
			return;
		}

		$msgs = $this->db->query("SELECT * FROM server_msg_queues WHERE id_charge_point = {$charge_point['id']} ORDER BY created_at DESC");
		if(empty($msgs)) {
			return null;
		}

		return $msgs[0];
	}
}
