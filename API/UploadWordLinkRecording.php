<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php'); // TODO might not required
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    RespondWithError(405, "Request must be a POST");
}

if (!(
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('term', $_POST) &&
    array_key_exists('textBackTranslation', $_POST))
) {
    RespondWithError(400, 'This endpoint requires PhoneId, term and textBackTranslation.');
}

$androidId = trim($_POST['PhoneId']);
$term = trim($_POST['term']);
$textBackTranslation = trim($_POST['textBackTranslation']);

$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);
if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadWordLinkRecording.php");
}

$recordingId = $model->CreateOrUpdateWordLinkRecording(
        $projectId,
        $term,
        $textBackTranslation);

echo json_encode(['RecordingId' => $recordingId]);