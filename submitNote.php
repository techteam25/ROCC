<?php

require_once('API/utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $notes = $_POST['notes'];
    $slideNumber = $_POST['slideNumber'];
    $storyId = $_POST['storyId'];

    $conn = GetDatabaseConnection();

    $sql = "UPDATE Slide
        SET note = ?
        WHERE slideNumber = ?
        AND storyId = ?"; 
    
    $stmt = PrepareAndExecute($conn, $sql, array($notes, $slideNumber, $storyId));
} else {
    http_response_code(405);
    die();
}

