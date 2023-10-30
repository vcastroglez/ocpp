<?php
// conected MySQL
function env(string $var, mixed $default): mixed
{
	$env_file = __DIR__."/../../.env";
	foreach(file($env_file) as $elem) {
		$line = explode("=",trim($elem));
		if($line[0]==$var){
			return $line[1] ?? $default;
		}
	}

	return $default;
}
define('DB_HOST', env('DB_HOST','localhost'));
define('DB_USER', env('DB_USER','ocpp'));
define('DB_NAME', env('DB_NAME','ocpp'));
define('DB_PASS', env('DB_PASS','ocpp'));
define('DB_PORT', env('DB_PORT','3609'));

define('HEARTBEAT_TIME', env('HEARTBEAT_TIME',60));