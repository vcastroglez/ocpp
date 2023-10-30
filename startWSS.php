<?
//Ratchet Chat WSS server
use ChessServer\Socket;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\SecureServer;

use MyApp\Desc;
use MyApp\MySQL;
use MyApp\Init;

require 'vendor/autoload.php';

$loop = Factory::create();
$server = new Server('0.0.0.0:2040', $loop);

$secureServer = new SecureServer($server, $loop, [
    'local_cert'  => '/url.crt',
    'local_pk' => '/url.key',
    'verify_peer' => false,
]);

$socket = new Desc();
$httpServer = new HttpServer(new WsServer( $socket  ));

$ioServer = new IoServer($httpServer, $secureServer, $loop);

//When the wss connection is active, start a timer to check and send commands to the station
$ioServer->loop->addPeriodicTimer(5, function () use ($socket)
{
  foreach($socket->clients as $client)
  {
    $init = new Init();
    $send = $init->SetCommand($socket->getIdTag($client));
    if($send)
    {
        if ($send['idTag'] === $socket->getIdTag($client))
        {
          $init->UpUserCommand($socket->getIdTag($client), $send['user_id']);

          echo 'CS - user_id - '.$send['user_id'].' - '.$socket->getIdTag($client).' '.date('H:i:s').' '.$send['text'].PHP_EOL.PHP_EOL;
          //$socket->onMessageTimer($client, $send['text']);
          $client->send($send['text']);
          $init->up_command($send['text'], $socket->getIdTag($client));
        }
    }
  }
});
//When the wss connection is active, start a timer to check and send commands to the station

$ioServer->run();
