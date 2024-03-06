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
        RespondWithError(400, "Please register a project using /API/RegisterPhone.php before using /API/UploadWordLinkRecording.php");
    }

    // get wordlink recording
    $wordlinkRecoding = $model->GetWordLinkRecording($projectId, $term);

    if(!$wordlinkRecoding) {
        Respond\error("Word link recording not found.");
        exit;
    }
             
    $audioFile = "Projects/". $androidId . "/WordLinks/" . $wordlinkRecoding['fileName'];

    $audioFileLink = "";
    // return the audio file link if exists
    if (file_exists($GLOBALS['filesRoot'] . "/" . $audioFile) ) {
        $audioFileLink = "Files/". $audioFile;
    }

    $data = [
        'audioFileLink' => $audioFileLink,
        'backTranslation' => $wordlinkRecoding['textBackTranslation'],
    ];
    
    Respond\successData($data);
}


