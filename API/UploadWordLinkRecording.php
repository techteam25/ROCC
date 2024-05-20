<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');

use storyproducer\Respond;

require_once('utils/Validate.php');
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


if (!is_array($_POST['textBackTranslation'])) {
    RespondWithError(400, 'Please send textBackTranslactions as repeated items with the key \'textBackTranslation[].\'');
}


$androidId = trim($_POST['PhoneId']);
$term = trim($_POST['term']);


// trim each value in the array
$textBackTranslations = array_map('trim', $_POST['textBackTranslation']);

// filter null and empty values
$textBackTranslations = array_values(array_filter($textBackTranslations, function ($value) {
    return $value !== null && $value !== '' && $value !== false;
}));


$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);

// delete existing wordLinkRecording if no textBackTranslation provided
if (empty($textBackTranslations)) {
    if ($model->DeleteWordLinkRecording($projectId, $term)) {
        Respond\success();
        exit;
    }

    RespondWithError(500, "Failed to delete WordLinkRecording");
}


if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadWordLinkRecording.php");
}

$recordingId = $model->CreateOrUpdateWordLinkRecording(
    $projectId,
    $term,
    json_encode($textBackTranslations));

echo json_encode(['RecordingId' => $recordingId]);