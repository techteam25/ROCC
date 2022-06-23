<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = Validate\moveResource($_POST);
    } catch(\Exception $e){
        die();
    }

    $model = new Model();
    $model->CopyFile($input['SourceFilename'], $input['DestFilename'], $input['Container']);
    $model->DeleteFile($input['SourceFilename'], $input['Container']);

    Respond\success();

} else {
    die();
}

