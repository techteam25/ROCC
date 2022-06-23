<?php
require_once('utils/Model.php');



    $text = $_POST['text'];
    $slideNum = $_POST['slideNumber'];
    $storyId = $_POST['storyId'];
 
    echo $text;
    echo $slideNum;
    echo $storyId;

    session_start();
    
    $conn = GetDatabaseConnection();
    $sql = "INSERT INTO Messages (storyId, slideNumber, isConsultant, isUnread, isTranscript, text) VALUES (?,?,true,true,false,?)";
    $stmt = PrepareAndExecute($conn, $sql, array($storyId, $slideNum, $text));
?>
