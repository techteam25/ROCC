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
        $this->clients = array();
        $this->conn = GetDatabaseConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
        $url = parse_url($conn->httpRequest->getUri());
        $path_elements = explode('/', $url['path'], 4);
        error_log(json_encode($path_elements));
        $client = (object)array();
        $client->conn = $conn;
        $client->isConsultant = $path_elements[1] === 'consultant';
        $client->projectId = $path_elements[2];
        if ($client->isConsultant) {
            $client->storyId = intval($path_elements[3]);
	} else {
	    $client->storyId = 0;
	}
        error_log(json_encode($client));

        //Check to see if there is already a connection to this phone ($isConsultant = false). If so, remove from list
	if ($client->isConsultant == false) {
            foreach($this->clients as $tmpClient) {
                if ($tmpClient->projectId === $client->projectId && 
		        $tmpClient->isConsultant === $client->isConsultant &&
                        $tmpClient->storyId === $client->storyId) {
		    error_log("Duplicate connection " . $tmpClient->conn->resourceId . " to phone " . $client->projectId);
	        }
	    }
	}
        $this->clients[$conn->resourceId] = $client;
        error_log($conn->resourceId . ": got new connection {$conn->httpRequest->getUri()}");
    }

    public function onMessage(ConnectionInterface $conn, $messageString) {
        error_log("from {$conn->resourceId}: $messageString");
        $currentClient = $this->clients[$conn->resourceId];

        $messageData = json_decode($messageString);
        $type = $messageData->type;
        if ($type === "text") {
            $storyId = intval($messageData->storyId);
            $slideNumber = $messageData->slideNumber;
            $isConsultant = $currentClient->isConsultant;
            if (!empty($messageData->isTranscript)) {
                $isTranscript = $messageData->isTranscript === true;
	    } else {
		$isTranscript = false;
	    }
            $text = $messageData->text;

            $currentTimestamp = date('Y-m-d H:i:s');
            error_log("from {$conn->resourceId}: $currentTimestamp");
            $stmt = PrepareAndExecute($this->conn,
                'INSERT INTO Messages (storyId, slideNumber, isConsultant, isUnread, isTranscript, text)
                VALUES (?,?,?,true,?,?)',
                array($storyId, $slideNumber, (int)$isConsultant, (int)$isTranscript, $text));

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
                    error_log("To(1): " . $client->conn->resourceId);
                    error_log("Tmp: $message");
                    error_log("Prj: $client->projectId");
                    error_log("Cns: $client->isConsultant");
                    error_log("Sty: $client->storyId");
                    $client->conn->send($message);
                }
            }
        } else if ($type === "catchup") {
	    if (!empty($messageData->since)) {
		$since = $messageData->since;
	    } else {
		$since = 0;
	    }
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
                error_log("To(2): " . $currentClient->conn->resourceId);
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
                error_log("To(3): " . $currentClient->conn->resourceId);
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
                    error_log("To(4): " . $client->conn->resourceId);
                    error_log($message);
                    $client->conn->send($message);
                }
            }
            FCMSend($storyId, $currentClient->projectId);
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

function FCMSend(int $storyId, $projectId) {
    $conn = GetDatabaseConnection();
    $SQL = 'SELECT SUM(isApproved) AS Approved, COUNT(isApproved) AS Total, title FROM Stories ' .
	    'LEFT JOIN Slide ON Stories.id = Slide.storyId WHERE Stories.id = ' . $storyId;
    $Pct = PrepareAndExecute($conn, $SQL, array());
    while (($row = $Pct->fetch(PDO::FETCH_ASSOC))) {
        // according to Robin... the ROCC user needs to approve the song slide even if it is blank - 10/7/22
	$PctApproved = (int)($row['Approved'] / ($row['Total'] - 0) * 100);
	$Title = $row['title'];
    }
    if ($PctApproved == 100) {   // 100% of slides approved
	error_log("projectID: " . $projectId);
        $SQL = 'SELECT fcmToken FROM Projects WHERE androidId = "' . $projectId . '"';
        $FCMToken = PrepareAndExecute($conn, $SQL, array());
	$fcmToken = "";
        while (($row = $FCMToken->fetch(PDO::FETCH_ASSOC))) {
	    $fcmToken = $row['fcmToken'];
	}
        if ($fcmToken != "") {
            time_nanosleep(0, 250000000);
            $cmd = 'curl -X POST --header "Authorization: key=AAAAU8MDzIQ:APA91bEm-Xskg66XnJXnUe5MvFs60eHiq-14eCiZ3n7atak-mbYcz7idkWQ7OB1IDDsQV0TPWhixEX_StNGCZUemP805qd4vzKndmvuAMcvfmr35gZZTzN3qVeXsBnmB3lGHZB-9QdVT "     --Header "Content-Type: application/json"     https://fcm.googleapis.com/fcm/send -d "{\"to\":\"' . $fcmToken . '\",\"notification\":{\"title\":\"Story Producer Adv\",\"body\":\"Story - ' . $Title . ' - ' . $PctApproved . '% audio files approved.\"}}"';
            $result = shell_exec($cmd);
            error_log("100% approved, notification: " . $result);
	}
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
