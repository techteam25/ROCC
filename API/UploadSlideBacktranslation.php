<?php
require_once('utils/Model.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    RespondWithError(405, "Request must be a POST");
}

if (!(array_key_exists('Key', $_POST) &&
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('TemplateTitle', $_POST) &&
    array_key_exists('Data', $_POST))) {

    RespondWithError(400, 'This endpoint requires Key, PhoneId, Data, TemplateTitle');
}

function InitializeNewStory($conn, $androidId, $templateTitle) {
    error_log("Attempting to initialize story with template $templateTitle");

    $projectIdStmt = PrepareAndExecute($conn, 'SELECT id FROM Projects WHERE androidId = ?', array($androidId));
    if (!($row = $projectIdStmt->fetch(PDO::FETCH_ASSOC))) {
        RespondWithError(400, "Project must be registered before audio can be uploaded");
    }
    $projectId = $row['id'];

    if (!$conn->beginTransaction()) {
        RespondWithError(500, "Failed to begin database transaction");
    }

    $checkExistingStoryStmt = PrepareAndExecute($conn,
        'SELECT id FROM Stories WHERE title = ? AND projectId = ?',
        array($templateTitle, $projectId));

    if (($row = $checkExistingStoryStmt->fetch(PDO::FETCH_ASSOC))) {
        error_log("Founding existing story with same template title and project id");
        return $row['id'];
    }

    PrepareAndExecute($conn, 'INSERT INTO Stories (title, projectId, note) SELECT ?,?,""', array($templateTitle, $projectId));
    $storyId = $conn->lastInsertId();

    error_log("Parsing template project file");
    //$templateDirectory = "../Files/Templates/$templateTitle";
    $templateDirectory = "{$GLOBALS['filesRoot']}/Templates/$templateTitle";
    echo $templateDirectory;
    if (!file_exists($templateDirectory) || !is_dir($templateDirectory)) {
        RespondWithError(400, "Server does not contain requested template.");
    }

//  this was for XML templates
//  $projectDocument = new DOMDocument;
//  $projectDocument->preserveWhiteSpace = false;
//  $projectDocument->load("$templateDirectory/project.xml");
//    $projectXPath = new DOMXPath($projectDocument);
//    $projectXPath->registerNamespace('m', 'MSPhotoStory');
//    $slideEntries = $projectXPath->query('/m:MSPhotoStoryProject/m:VisualUnit');

//  this was for json templates
    $storyJson = file_get_contents("$templateDirectory/project/story.json");
    $decode_story = json_decode($storyJson, true);
    $slideEntries = $decode_story['slides'];
    $slideIndex = 0;

    error_log("Copying template '$templateTitle' folder to new project directory and creating slides");
    foreach ($slideEntries as $slideEntry) {

        if ($slideEntry[slideType] !== 'COPYRIGHT') {
        error_log("Got slide number $slideIndex");
        PrepareAndExecute($conn,
            'INSERT IGNORE INTO Slide (storyId, note, slideNumber, isApproved) VALUES (?,"",?,0)', 
            array($storyId, $slideIndex));
        }
        $slideIndex++;
    }
    if (!$conn->commit()) {
        RespondWithError(500, "Failed to commit database transaction");
    }
    return $storyId;
}


$androidId = $_POST['PhoneId'];
$templateTitle = htmlspecialchars(trim($_POST['TemplateTitle']));
$audioData = base64_decode($_POST['Data']);

$conn = GetDatabaseConnection();

if (array_key_exists('StoryId', $_POST)) {
    $storyId = $_POST['StoryId'];
    $findStoryStmt = PrepareAndExecute($conn, 'SELECT id FROM Stories WHERE id = ?', array($storyId));
    if (!$findStoryStmt->fetch(PDO::FETCH_ASSOC)) {
        error_log("Could not find a story with StoryId $storyId. Creating it now.");
        InitializeNewStory($conn, $androidId, $templateTitle);
    }
} else {
    $storyId = InitializeNewStory($conn, $androidId, $templateTitle);
}

$directory = "Projects/$androidId/$storyId";

if (array_key_exists('IsWholeStory', $_POST) && $_POST['IsWholeStory'] === "true") {
    PutFile($directory, "wholeStory.m4a", $audioData);
} else if (array_key_exists('SlideNumber', $_POST)){
    $slideNumber = $_POST['SlideNumber'];
    PutFile($directory, "$slideNumber.m4a", $audioData);
} else {
    RespondWithError(400, "Either IsWholeStory or SlideNumber is required to be in the request");
}

echo json_encode(array('StoryId' => $storyId));
