<?php

require_once('API/utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $slideNumber = $_POST['slideNumber'];
    $storyId = $_POST['storyId'];
    
    $conn = GetDatabaseConnection();
    $sql = "SELECT note from Slide WHERE slideNumber=? AND storyId=? ";
    $stmt = PrepareAndExecute($conn, $sql, array($slideNumber, $storyId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['note'];
}


