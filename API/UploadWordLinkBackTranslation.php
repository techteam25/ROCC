<?php
require_once('utils/Model.php');
require_once('utils/Validate.php');

use storyproducer\Validate;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    RespondWithError(405, "Request must be a POST");
}

if (!(
    array_key_exists('PhoneId', $_POST) &&
    array_key_exists('term', $_POST))
) {
    RespondWithError(400, 'This endpoint requires PhoneId, term and textBackTranslation.');
}


$textBackTranslations = Validate\processTextBackTranslations($_POST);

if (empty($textBackTranslations)) {
    RespondWithError(400, "Please provide at least one item with the key 'textBackTranslation'");
}


$androidId = trim($_POST['PhoneId']);
$term = trim($_POST['term']);


$model = new Model();
# check if project exists for the given androidId
$projectId = $model->GetProjectId($androidId);

if (!$projectId) {
    RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadWordLinkBackTranslation.php");
}

$translationId = $model->CreateOrUpdateWordLinkBackTranslation(
    $projectId,
    $term,
    json_encode($textBackTranslations));

echo json_encode(['TranslationId' => $translationId]);