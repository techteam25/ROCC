<?php
require_once('utils/Model.php');
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
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/DeleteTextBackTranslation.php");
}

try {
    $statusCode = 204;

    if (!$model->DeleteTextBacktranslation($projectId, $term)) {
        $statusCode = 404;
    }

    http_response_code($statusCode);
    exit;
} catch (\Exception $e) {
    $message = "There was an exception while deleting the text back translation: " . $e->getMessage();
    $statusCode = 500;
}

RespondWithError(500, $message);