<?php

require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $storyId = $_POST['storyId'];
    
    $conn = GetDatabaseConnection();
    $sql = "SELECT note from Stories WHERE id=? ";
    $stmt = PrepareAndExecute($conn, $sql, array($storyId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['note'];
}


