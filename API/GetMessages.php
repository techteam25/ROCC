<?php
require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn = GetDatabaseConnection();
    $phoneId = $_POST['PhoneId'];

    // TODO @pwhite: Why does that Messages table have a slideNumber field when
    // we have this data already stored in the slide table. We should create
    // the slide record if a message is sent for that slide.
    $stmt = PrepareAndExecute($conn, 
        'SELECT storyId, slideNumber, isConsultant, isUnread, isTranscript, text 
         FROM Messages, Stories, Projects
         WHERE Messages.storyId = Stories.id
           AND Stories.projectId = Projects.id
           AND Projects.androidId = ?', array($phoneId));
    $index = 0;
    $messages = array();
    while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $messages[$index] = $row;
        $index++;
    }

    $stmt2 = PrepareAndExecute($conn,
        'SELECT storyId, slideNumber, isApproved
         FROM Slide, Stories, Projects
         WHERE Slide.storyId = Stories.id
           AND Stories.projectId = Projects.id
           AND Projects.androidId = ?', array($phoneId));
    $index2 = 0;
    $approvals = array();
    while (($row2 = $stmt2->fetch(PDO::FETCH_ASSOC))) {
        $approvals[$index2] = $row2;
        $index2++;
    }

    echo json_encode(array(
        'Messages' => $messages,
        'Approvals' => $approvals
    ));
} else {
	// not a POST request
	http_response_code(405);
	die();
}
