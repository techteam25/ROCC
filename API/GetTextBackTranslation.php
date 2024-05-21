<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');

use storyproducer\Respond;

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!(array_key_exists('PhoneId', $_POST) &&
        array_key_exists('term', $_POST))) {
        RespondWithError(400, 'This endpoint requires PhoneId and term');
    }

    $androidId = htmlspecialchars($_POST['PhoneId']);
    $term = htmlspecialchars($_POST['term']);

    $model = new Model();

    # check if project exists for the given androidId
    $projectId = $model->GetProjectId($androidId);
    if (!$projectId) {
        RespondWithError(404, "The requested PhoneId has not been registered.");
    }

    $translation = $model->GetTextBackTranslation($projectId, $term);

    if (!$translation) {
        RespondWithError(404, "The requested term has not been uploaded from the requested PhoneId.");
        exit;
    }

    $data = [
        'backTranslation' => json_decode($translation['textBackTranslation']),
    ];

    Respond\successData($data);
}


