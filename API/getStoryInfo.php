<?php

require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $storyId= $_POST['storyId'];
    $conn = GetDatabaseConnection();
    $sql = 'SELECT isApproved FROM Slide WHERE storyId = ?';
    $stmt = PrepareAndExecute($conn, $sql, array($storyId));
    $data_arr = []; 
    while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
	array_push($data_arr, array("isApproved" => $row['isApproved']));
    }
    echo json_encode($data_arr);
}

