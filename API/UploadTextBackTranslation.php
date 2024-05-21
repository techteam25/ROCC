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


if (empty($textBackTranslations)) {
    RespondWithError(400, "Please provide at least one item with the key 'textBackTranslation[]'");
}


$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);

if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadTextBackTranslation.php");
}

$translationId = $model->CreateOrUpdateTextBackTranslation(
    $projectId,
    $term,
    json_encode($textBackTranslations));

echo json_encode(['translationId' => $translationId]);