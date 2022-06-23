<?php

require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $notes = $_POST['notes'];
    $id = $_POST['storyId'];

    $conn = GetDatabaseConnection();

    $sql = "UPDATE Stories
        SET note = ?
        WHERE id = ?"; 
    
    $stmt = PrepareAndExecute($conn, $sql, array($notes, $id));
} else {
    http_response_code(405);
    die();
}
