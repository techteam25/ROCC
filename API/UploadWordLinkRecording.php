<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    RespondWithError(405, "Request must be a POST");
}

if (!(
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('term', $_POST) &&
    array_key_exists('Data', $_POST) &&
    array_key_exists('wordLinkRecording', $_POST) &&
    array_key_exists('audioRecordingFilename', $_POST['wordLinkRecording']) &&
    array_key_exists('textBackTranslation', $_POST['wordLinkRecording']))
) {

    RespondWithError(400, 'This endpoint requires PhoneId, term, Data, audioRecordingFilename, textBackTranslation.');
}

$androidId = trim($_POST['PhoneId']);
$term = trim($_POST['term']);
$audioRecordingFilename = htmlspecialchars(trim($_POST['wordLinkRecording']['audioRecordingFilename']));
$textBackTranslation = htmlspecialchars(trim($_POST['wordLinkRecording']['textBackTranslation']));

// extract & validate audio recording file extension
$audioRecordingFileExtension = pathinfo($audioRecordingFilename, PATHINFO_EXTENSION);
if (!preg_match('/^[A-Za-z0-9]{1,100}$/', $audioRecordingFileExtension))
{
    RespondWithError(400, "File extension should match /^[A-Za-z0-9]{1,100}$/");
}

$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);
if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadWordLinkRecording.php");
}

$recordingId = $model->CreateOrUpdateWordLinkRecording(
        $projectId,
        $androidId,
        $term,
        $textBackTranslation,
        $audioRecordingFilename,
        base64_decode($_POST['Data']));

echo json_encode(['RecordingId' => $recordingId]);