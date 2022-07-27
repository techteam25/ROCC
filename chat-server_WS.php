<?php

ini_set("log_errors", 1);
ini_set("error_log", "/dev/stderr");

require_once(dirname(__FILE__).'/vendor/autoload.php');
require_once('API/utils/Model.php');

use Ratchet\Server\IoServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class MessageHandler implements MessageComponentInterface {
    protected $clients;
    protected $conn;

    public function __construct() {
error_log("#1");
        $this->clients = array();
        $this->conn = GetDatabaseConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
error_log("#2");
        $url = parse_url($conn->httpRequest->getUri());
error_log("#3" . $url['path']);
        $path_elements = explode('/', $url['path'], 4);
        error_log(json_encode($path_elements));
        $client = (object)array();
        $client->conn = $conn;
        $client->isConsultant = $path_elements[1] === 'consultant';
error_log("#4" . count($path_elements));
        $client->projectId = $path_elements[2];
        if ($client->isConsultant) {
            $client->storyId = intval($path_elements[3]);
        }
        error_log(json_encode($client));

        $this->clients[$conn->resourceId] = $client;
        error_log("got new connection {$conn->httpRequest->getUri()}");
    }

    public function onMessage(ConnectionInterface $conn, $messageString) {
        error_log("from {$conn->resourceId}: $messageString");
        $currentClient = $this->clients[$conn->resourceId];

        $messageData = json_decode($messageString);
        $type = $messageData->type;
error_log("type: " . $type);	
        if ($type === "text") {
            $storyId = intval($messageData->storyId);
            if ($storyId === null) {
                $storyId = 0;
            }
            $slideNumber = $messageData->slideNumber;
            $isConsultant = $currentClient->isConsultant;
            $isTranscript = $messageData->isTranscript === true;
            $text = $messageData->text;

            $currentTimestamp = date('Y-m-d H:i:s');
            error_log("from {$conn->resourceId}: $currentTimestamp");
error_log("#6");
            $stmt = PrepareAndExecute($this->conn,
                'INSERT INTO Messages (storyId, slideNumber, isConsultant, isUnread, isTranscript, timeSent, text)
                VALUES (?,?,?,true,?,?,?)',
                array($storyId, $slideNumber, (int)$isConsultant, (int)$isTranscript, $currentTimestamp, $text));

            $message = json_encode(array(
                'type' => 'text',
                'storyId' => $storyId,
                'slideNumber' => $slideNumber,
                'isConsultant' => $isConsultant,
                'isTranscript' => $isTranscript,
                'timeSent' => $currentTimestamp,
                'text' => $text,
            ));

            foreach($this->clients as $client) {
                if ($client->projectId === $currentClient->projectId && 
                    (!$client->isConsultant || $client->storyId === $storyId)) {
                    $client->conn->send($message);
                }
            }
        } else if ($type === "catchup") {
            $since = $messageData->since;
            if (!$since) {
                $since = '1970-01-01 00:00:00';
            }
            if ($currentClient->isConsultant) {
                error_log("consultant: sending all messages for this story");
                $messagesStatement = PrepareAndExecute($this->conn,
                    'SELECT * FROM Messages WHERE storyId = ? AND timeSent > ?',
                    array($currentClient->storyId, $since));
                $approvalsStatement = PrepareAndExecute($this->conn,
                    'SELECT storyId, slideNumber, isApproved, lastApprovalChangeTime FROM Slide 
                    WHERE storyId = ? AND lastApprovalChangeTime > ?',
                    array($currentClient->storyId, $since));
            } else {
                error_log("from phone: sending all messages for project {$currentClient->projectId}");
                $messagesStatement = PrepareAndExecute($this->conn,
                    'SELECT * FROM Messages 
                    JOIN Stories ON Messages.storyId = Stories.id
                    JOIN Projects on Stories.projectId = Projects.id
                    WHERE Projects.androidId = ? AND timeSent > ?',
                    array($currentClient->projectId, $since));
                $approvalsStatement = PrepareAndExecute($this->conn,
                    'SELECT storyId, slideNumber, isApproved, lastApprovalChangeTime FROM Slide 
                    JOIN Stories ON Slide.storyId = Stories.id
                    JOIN Projects on Stories.projectId = Projects.id
                    WHERE Projects.androidId = ? AND lastApprovalChangeTime > ?',
                    array($currentClient->projectId, $since));
            }
            while (($row = $messagesStatement->fetch(PDO::FETCH_ASSOC))) {
                $message = json_encode(array(
                    'type' => 'text',
                    'storyId' => $row['storyId'],
                    'slideNumber' => $row['slideNumber'],
                    'isConsultant' => $row['isConsultant'] === '1' ? true : false,
                    'isTranscript' => $row['isTranscript'] === '1' ? true : false,
                    'timeSent' => $row['timeSent'],
                    'text' => $row['text']
                ));
                error_log($message);
                $currentClient->conn->send($message);
            }
            while (($row = $approvalsStatement->fetch(PDO::FETCH_ASSOC))) {
                $message = json_encode(array(
                    'type' => 'approval',
                    'storyId' => $row['storyId'],
                    'slideNumber' => $row['slideNumber'],
                    'approvalStatus' => $row['isApproved'] == '1' ? true : false,
                    'timeSent' => $row['lastApprovalChangeTime'],
                ));
                error_log($message);
                $currentClient->conn->send($message);
            }
        } else if ($type === 'approval' && $currentClient->isConsultant) {
            $slideNumber = $messageData->slideNumber;
            $storyId = $currentClient->storyId;
            $isApproved = $messageData->approvalStatus === true ? true : false;
            $currentTimestamp = date('Y-m-d H:i:s');
            PrepareAndExecute($this->conn,
                "UPDATE Slide SET isApproved = ?, lastApprovalChangeTime = ?
                WHERE slideNumber = ? AND storyId = ?",
                array((int)$isApproved, $currentTimestamp, $slideNumber, $storyId));

            $message = json_encode(array(
                'type' => 'approval',
                'storyId' => $storyId,
                'slideNumber' => $slideNumber,
                'approvalStatus' => $isApproved,
                'timeSent' => $currentTimestamp,
            ));

            foreach($this->clients as $client) {
                if ($client->projectId === $currentClient->projectId && 
                    (!$client->isConsultant || $client->storyId === $storyId)) {
                    $client->conn->send($message);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
        error_log("Connection to {$conn->resourceId} has closed");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        error_log("Error: {$e->getMessage()}");
    }
}

# $port = $GLOBALS['websocketPort'];
# $ioServer = IoServer::factory(new HttpServer(new WsServer(new MessageHandler())), $port);
# error_log("server started on port $port");
# $ioServer->run();

$port = $GLOBALS['websocketPort'];
$loop = React\EventLoop\Factory::create();
$server = new React\Socket\Server("0.0.0.0:$port", $loop);
$server->on('error', function(Exception $e) {
    error_log("error: {$e->getMessage()}");
});
//$secureServer = new React\Socket\SecureServer($server, $loop, [
    //'allow_self_signed' => false,
    //'verify_peer' => false
//]);

$ioServer = new Ratchet\Server\IoServer(new HttpServer(new WsServer(new MessageHandler())), $server, $loop);
error_log("server started on port $port");
$ioServer->run();
