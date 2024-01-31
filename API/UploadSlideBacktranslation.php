<?php
require_once('utils/Model.php');
require_once('utils/MailROCC.php');

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

    if (array_key_exists('Language', $_POST)) {
        $language = $_POST['Language'];
    } else {
        $language = "";
    }
    PrepareAndExecute($conn, 'INSERT INTO Stories (title, language, projectId, note) SELECT ?,?,?,""', array($templateTitle, $language, $projectId));
    $storyId = $conn->lastInsertId();

    error_log("Parsing template project file");
    //$templateDirectory = "../Files/Templates/$templateTitle";
    $templateDirectory = "{$GLOBALS['filesRoot']}/Templates/";
    if ($language !== "") {
        $templateDirectory = $templateDirectory . $language . "/";
    }
    $templateDirectory = $templateDirectory . $templateTitle;
    error_log(sprintf("Template Directory for given story:%s: %s", $storyId, $templateDirectory));
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
    $storyJson1 = file_get_contents("$templateDirectory/project/story.json");
    $storyJson = str_replace('\n', "<BR>", $storyJson1);
    $decode_story = json_decode($storyJson, true);
    $slideEntries = $decode_story['slides'];
    $slideIndex = 0;

    error_log("Copying template '$templateTitle' folder to new project directory and creating slides");
    foreach ($slideEntries as $slideEntry) {

        if ($slideEntry['slideType'] !== 'COPYRIGHT') {
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

function CheckEmailNotify($conn, $storyId, $androidId) {
    $total = GetNumberOfSlides($conn, $storyId);
    // number of non-required slides is also in function SlideAudioExists below
    $totalReq = $total - 1;
    $count = CountReqAudioFiles($total, $storyId, $androidId);

    $stmt = "SELECT title, FirstThreshold, SecondThreshold, Projects.language FROM Stories ";
    $stmt = $stmt . "LEFT JOIN Projects on Stories.ProjectId =  Projects.id ";
    $stmt = $stmt . "WHERE Stories.id = ?";

    $projectIdStmt = PrepareAndExecute($conn, $stmt, array($storyId));
	
    if (($row = $projectIdStmt->fetch(PDO::FETCH_ASSOC))) {
	if (($row['FirstThreshold'] == null && $count / $totalReq >= .5) ||
	    ($row['SecondThreshold'] == null && $count + 1 >=  $totalReq ))
        {  // Thresold reached, email user
            $From = "Story Producer Adv <noreply@techteam.org>";
            $Pct = strval($count) . " of " . strval($totalReq);
            $Message = "$Pct required audio files have been uploaded for ";
            $Message = $Message . "Language: " . $row['language'] . " ";
            $Message = $Message . "Story: " . $row['title'];

            $Subject = "Audio file upload status";
            $To = getConsultantInfo($conn, $androidId);
            SendMailRoccUser($From, $To, $Subject, $Message);
        }
        $dt = date('Y-m-d H:i:s');
	if ($row['FirstThreshold'] == null && $count / $totalReq >= .5) // save timestamp
	{
            $sql = "UPDATE Stories SET FirstThreshold = ?  WHERE id = ?"; 
            $stmt = PrepareAndExecute($conn, $sql, array($dt, $storyId));
	}
	if ($row['SecondThreshold'] == null && $count + 1 >=  $totalReq ) // save timestamp
	{
            $sql = "UPDATE Stories SET SecondThreshold = ?  WHERE id = ?"; 
            $stmt = PrepareAndExecute($conn, $sql, array($dt, $storyId));
	}
    }
}

function getConsultantInfo($conn, $androidId) {
    $projectIdStmt = PrepareAndExecute($conn, 'SELECT Consultants.name, Consultants.email ' .
	' FROM Projects LEFT JOIN Assigned ON Projects.id = Assigned.ProjectId ' .
	' LEFT JOIN Consultants ON Assigned.ConsultantId = Consultants.id ' .
	' WHERE androidId = ?', array($androidId));
    if (($row = $projectIdStmt->fetch(PDO::FETCH_ASSOC))) {
      $email = $row['name'] . '<' . $row['email'] . '>';
    }
    return $email;
}

function SlideAudioExists($slideNum, $storyId, $androidId) {
    $directory = "Projects/$androidId/$storyId";
    $file = "{$GLOBALS['filesRoot']}/$directory/" . $slideNum . ".m4a";
    return file_exists($file);
}

function CountReqAudioFiles($total, $storyId, $androidId) {
    $count = 0;
    for ($idx = 0; $idx < $total - 1; $idx++) {
        if (SlideAudioExists($idx, $storyId, $androidId))
            $count++;
    }
    return $count;
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
$sendEmail = false;

if (array_key_exists('IsWholeStory', $_POST) && $_POST['IsWholeStory'] === "true") {
    PutFile($directory, "wholeStory.m4a", $audioData);
} else if (array_key_exists('SlideNumber', $_POST)){
    $slideNumber = $_POST['SlideNumber'];
    PutFile($directory, "$slideNumber.m4a", $audioData);
    $sendEmail = true;
} else {
    RespondWithError(400, "Either IsWholeStory or SlideNumber is required to be in the request");
}

echo json_encode(array('StoryId' => $storyId));
if ($sendEmail == true) {
    CheckEmailNotify($conn, $storyId, $androidId);
}
