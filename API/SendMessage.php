<?php
require_once(dirname(__FILE__).'/../vendor/autoload.php');
require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log(json_encode($_POST));

    $conn = GetDatabaseConnection();
    // TODO @pwhite: Do some validation here.
    $text = $_POST['Data'];
    $slideNum = $_POST['SlideNumber'];
    $storyId = $_POST['StoryId'];
    $isTranscript = $_POST['IsTranscript'];
    $stmt = PrepareAndExecute($conn,
        'INSERT INTO Messages (storyId, slideNumber, isConsultant, isUnread, isTranscript, text)
         VALUES (?,?,false,true,?,?)',
        array($storyId, $slideNum, $isTranscript, $text));
    echo json_encode(array('StoryId' => $storyId));
} else {
	http_response_code(405);
	die();
}
