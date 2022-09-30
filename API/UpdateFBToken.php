<?php
require_once('utils/Model.php');

// TODO @pwhite: This endpoint does not have any authentication, so anyone could
// create a project. There should probably be at least some type of API key
// that limits access to this endpoint.

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('FirebaseToken', $_POST)) {

    $conn = GetDatabaseConnection();
    
    if (!$conn->beginTransaction()) {
        RespondWithError(500, "Failed to begin database transaction");
    }

    $checkExistingProjectStmt = PrepareAndExecute($conn,
        "SELECT id FROM Projects WHERE androidId = ?",
        array($_POST['PhoneId']));

    $projectId = null;

    if (($row = $checkExistingProjectStmt->fetch(PDO::FETCH_ASSOC))) {
        error_log("Project found. Updating token.");
        $projectId = $row['id'];
        PrepareAndExecute($conn, "UPDATE Projects SET fcmToken = ?  WHERE androidId = ?", array(&$_POST['FirebaseToken'], $_POST['PhoneId']));
    } else {
        error_log("Project not found. Wait for registration.");
    }
    if (!$conn->commit()) {
        RespondWithError(500, "Failed to commit database transaction");
    }
    echo "Success";
} else {
	http_response_code(405);
}
