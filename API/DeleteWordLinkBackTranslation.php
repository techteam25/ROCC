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
    RespondWithError(404, "Please double check the PhoneId. A project with \"$androidId\" was not found.");
}

try {
    if (!$model->DeleteWordLinkBackTranslation($projectId, $term)) {
        RespondWithError(404, "Please double check the term. A textBackTranslation with \"$term\" was not found.");
    }

    http_response_code(204);
    exit;
} catch (\Exception $e) {
    $message = "There was an exception while deleting the text back translation: " . $e->getMessage();
}

RespondWithError(500, $message);