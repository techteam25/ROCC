<?php

require_once('API/utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log(json_encode($_POST));

    $slideNumber = $_POST['slideNumber'];
    $storyId = $_POST['storyId'];
    $projectId = $_POST['projectId'];
    if ($_POST['isApproved'] === "true") {
        $isApproved = 1;
    } else {
        $isApproved = 0;
    }

    $conn = GetDatabaseConnection();

    $sql = "UPDATE Slide, Projects, Stories
        SET isApproved = ?
        WHERE slideNumber = ?
        AND storyId = ?
        AND Projects.androidId = ?
        AND Projects.id = Stories.projectId
        AND Slide.storyId = Stories.id";
    $stmt = PrepareAndExecute($conn, $sql, array($isApproved, $slideNumber, $storyId, $projectId));
} else {
	http_response_code(405);
	die();
}
?>
