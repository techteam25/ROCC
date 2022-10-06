<?php
require_once('utils/Model.php');

// TODO @pwhite: This endpoint does not have any authentication, so anyone could
// create a project. There should probably be at least some type of API key
// that limits access to this endpoint.

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('TranslatorEmail', $_POST) &&
    array_key_exists('TranslatorPhone', $_POST) &&
    array_key_exists('TranslatorLanguage', $_POST) &&
    array_key_exists('ProjectEthnoCode', $_POST) &&
    array_key_exists('ProjectLanguage', $_POST) &&
    array_key_exists('ProjectCountry', $_POST) &&
    array_key_exists('ProjectMajorityLanguage', $_POST) &&
    array_key_exists('ConsultantEmail', $_POST) &&
    array_key_exists('TrainerEmail', $_POST)) {

    $conn = GetDatabaseConnection();
    
    if (!$conn->beginTransaction()) {
        RespondWithError(500, "Failed to begin database transaction");
    }

    $checkExistingProjectStmt = PrepareAndExecute($conn,
        "SELECT id FROM Projects WHERE androidId = ?",
        array($_POST['PhoneId']));

    $projectId = null;

    if (($row = $checkExistingProjectStmt->fetch(PDO::FETCH_ASSOC))) {
        error_log("Project already exists. Updating values.");
        $projectId = $row['id'];
        PrepareAndExecute($conn,
            "UPDATE Projects
             SET ethnoCode = ?,
                 language = ?,
                 country = ?,
                 majorityLanguage = ?,
                 trainerEmail = ?,
                 email = ?,
                 phone = ?,
                 spokenLanguage = ?,
                 fcmToken = ?
             WHERE androidId = ?",
        array(&$_POST['ProjectEthnoCode'],
              &$_POST['ProjectLanguage'],
              &$_POST['ProjectCountry'],
              &$_POST['ProjectMajorityLanguage'],
              &$_POST['TrainerEmail'],
              &$_POST['TranslatorEmail'],
              &$_POST['TranslatorPhone'],
              &$_POST['TranslatorLanguage'],
              &$_POST['FirebaseToken'],
              &$_POST['PhoneId']));
    } else {
        PrepareAndExecute($conn,
            "INSERT INTO Projects (androidId, fcmToken, ethnoCode, language, country,
                                   majorityLanguage, trainerEmail,
                                   email, phone, spokenLanguage)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            array(&$_POST['PhoneId'],
                  &$_POST['FirebaseToken'],
                  &$_POST['ProjectEthnoCode'],
                  &$_POST['ProjectLanguage'],
                  &$_POST['ProjectCountry'],
                  &$_POST['ProjectMajorityLanguage'],
                  &$_POST['TrainerEmail'],
                  &$_POST['TranslatorEmail'],
                  &$_POST['TranslatorPhone'],
                  &$_POST['TranslatorLanguage']));
        $projectId = $conn->lastInsertId();
    }

    PrepareAndExecute($conn,
       "INSERT IGNORE INTO Assigned SELECT id, ? FROM Consultants WHERE email = ?",
       array($projectId, $_POST['ConsultantEmail']));

    if (!$conn->commit()) {
        RespondWithError(500, "Failed to commit database transaction");
    }

    echo "Success";
} else {
	http_response_code(405);
}
