<?php
require_once('utils/Model.php');
//echo json_encode($_SERVER);

$CURR_STORY = $_GET['CURR_STORY'];
$CURR_PROJ = $_GET['CURR_PROJ'];
$CURR_SLIDE = $_GET['CURR_SLIDE'];

session_start();

$conn = GetDatabaseConnection();
$sql = "SELECT DISTINCT isConsultant, isTranscript, text FROM Messages, Stories, Projects WHERE slideNumber=? AND storyId=? AND Projects.androidId=? AND Projects.Id=Stories.projectId";
$stmt = PrepareAndExecute($conn, $sql, array($CURR_SLIDE, $CURR_STORY, $CURR_PROJ));


$index = 0;
$result = array();

while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
    $result[$index] = $row;
    $index++;
}

    echo json_encode($result);

?>
