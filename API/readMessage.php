<?php

require_once('utils/Model.php');

session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storyId = $_POST['storyId'];
    $slideNumber = $_POST['slideNumber'];
    
    $conn = GetDatabaseConnection();

    $sql = "UPDATE Messages
        SET isUnread = ?
        WHERE storyId = ?
        AND slideNumber = ?";

    $stmt = PrepareAndExecute($conn, $sql, array(0, $storyId, $slideNumber));
} else {
    http_reponse_code(405);
    DIE();
}
