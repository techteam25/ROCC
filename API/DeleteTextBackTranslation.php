<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');

session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    RespondWithError(405, "Request must be a POST");
}

if (!(
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('term', $_POST))
) {
    RespondWithError(400, 'This endpoint requires PhoneId & term.');
}

$androidId = trim($_POST['PhoneId']);
$term = trim($_POST['term']);

$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);

if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadTextBackTranslation.php");
}


if ($model->DeleteTextBacktranslation($projectId, $term)) {
    exit;
}

RespondWithError(500, "Failed to delete WordLinkRecording");
