<?php
require_once(dirname(__FILE__).'/../vendor/autoload.php');
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

global $chatClients, $clients, $model;
$chatClients = [];
$clients = new \SplObjectStorage;
$model = new Model();

ini_set('session.use_cookies', 0);

class Chat implements MessageComponentInterface {
    public function __construct(React\EventLoop\LoopInterface $loop) {
    }

    public function onOpen(ConnectionInterface $conn) {
        global $clients, $chatClients;
        // echo "New connection: $conn->resourceId\n";
        $clients->attach($conn);
        $chatClients[$conn->resourceId] = array(
            'PhoneId'     => '',
            'StoryTitle'  => '',
            'SlideNumber' => '',
            'LastId'      => -1
        );
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        global $clients, $chatClients, $model;
        $input = json_decode($msg, true);
        $isMobileCall = array_key_exists('Key', $input);

        // Check the authentication
        if(isset($input['SessionId'])) {
            // If there is a current session
            if(session_status() == PHP_SESSION_ACTIVE) {
                session_abort();
                $_SESSION = array();
            }

            // Load the session being sent from
            session_id($input['SessionId']);
            session_cache_limiter('');
            session_start();

            // Check that the session is valid
            ob_start();
            try {
                Validate\ensureLoggedIn();
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();

            // echo "Website call\n";
        } else if($isMobileCall) {
            // Check the key
            ob_start();
            try {
                Validate\ensureKey($input['Key']);
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();

            // echo "Mobile call\n";
        } else {
            // If no authentication worked, then the user is not logged in
            ob_start();
            Respond\error("No user logged in", false);
            $error = ob_get_clean();

            $from->send($error);

            // echo "No authentication\n";
            session_abort();
            return;
        }
        
        // Update what slide the client is currently on
        $chatClients[$from->resourceId]['PhoneId']     = $input['PhoneId'];
        $chatClients[$from->resourceId]['StoryTitle']  = $input['StoryTitle'];
        $chatClients[$from->resourceId]['SlideNumber'] = $input['SlideNumber'];
        if(array_key_exists('LastId', $input)) {
            $chatClients[$from->resourceId]['LastId'] = $input['LastId'];
        }

        // Store these values for later (easier) use
        $chatClient = $chatClients[$from->resourceId];
        $phoneId = $chatClient['PhoneId'];
        $storyTitle = $chatClient['StoryTitle'];
        $slideNumber = $chatClient['SlideNumber'];
        $lastId = $chatClient['LastId'];

        if($input['Type'] == 'send') {
            // echo "Send call\n";

            // Validate the input data

            ob_start();
            try {
                if ($isMobileCall) {
                    $input = Validate\sendMessageMobile($input);
                } else {
                    $input = Validate\sendMessageWeb($input);
                }
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();

            // Add the message to database
            ob_start();
            try{
                $message = $input['Message'];
                if ($isMobileCall) {
                    $model->AddTranslatorMessage($phoneId, $storyTitle, $slideNumber, $message);
                } else {
                    $model->AddConsultantMessage($phoneId, $storyTitle, $slideNumber, $message);
                }
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();
    
            ob_start();
            Respond\success();
            $response = ob_get_clean();
            $from->send($response);

            // Update all the clients with the new message
            foreach($clients as $client) {
                $chatClient = $chatClients[$client->resourceId];
                $phoneId = $chatClient['PhoneId'];
                $storyTitle = $chatClient['StoryTitle'];
                $slideNumber = $chatClient['SlideNumber'];
                $lastId = $chatClient['LastId'];

                // Check to see if this client is viewing the same slide
                if($phoneId == $input['PhoneId'] && $storyTitle == $input['StoryTitle'] && $slideNumber == $input['SlideNumber']) {
                    // Refresh the messages for all clients viewing the slide
                    ob_start();
                    try {
                        $messages = $model->GetMessages($phoneId, $storyTitle, $slideNumber, $lastId);
                    } catch(\Exception $e) {
                        $error = ob_get_clean();

                        $client->send($error);
                    }
                    ob_clean();

                    // Send the messages
                    ob_start();
                    Respond\successData($messages);
                    $response = ob_get_clean();
                    $client->send($response);

                    // Update the last message sent
                    $chatClients[$client->resourceId]['LastId'] = $messages['LastId'];
                }
            }
        } else if ($input['Type'] == 'get') {
            // echo "Get call\n";

            // Retrieve the last message sent through websockets
            $input['LastId'] = $lastId;

            // Check the input data
            ob_start();
            try {
                if ($isMobileCall) {
                    $input = Validate\getMessagesMobile($input);
                } else {
                    $input = Validate\getMessagesWeb($input);
                }
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();

            // Retrieve all the new messages
            ob_start();
            try {
                $messages = $model->GetMessages($phoneId, $storyTitle, $slideNumber, $lastId);
            } catch(\Exception $e) {
                $error = ob_get_clean();

                $from->send($error);
                session_abort();
                return;
            }
            ob_clean();
            
            // Send the new messages
            ob_start();
            Respond\successData($messages);
            $response = ob_get_clean();
            $from->send($response);

            // Update the last message sent
            $chatClients[$from->resourceId]['LastId'] = $messages['LastId'];
            session_abort();
            return;
        } else {
            ob_start();
            Respond\error("Unknown command");
            $error = ob_get_clean();
            $from->send($error);

            // echo "Unkown command\n";
            session_abort();
            return;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        global $clients, $chatClients;
        // echo "Closing connection: $conn->resourceId\n";
        unset($chatClients[$conn->resourceId]);
        $clients->detach($conn);
        session_abort();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
        // echo "Error Exception: $conn->resourceId\n";
        var_dump($e);
        // echo "\n";
    }
}

// Setup the websocket server
$loop = React\EventLoop\Factory::create();
$chat = new Chat($loop);
$ws = new WsServer($chat);
$server = IoServer::factory(new HttpServer($ws), 2508);
$ws->setStrictSubProtocolCheck(false);
$ws->enableKeepAlive($server->loop, 60);
$server->run();