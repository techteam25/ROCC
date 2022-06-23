<?php
require_once('API/utils/Model.php');
$conn = GetDatabaseConnection();
$stmt = PrepareAndExecute($conn, 'SELECT androidId FROM Projects', array());
while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
    echo "{$row['androidId']}\n";
}
