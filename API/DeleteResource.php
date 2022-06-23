<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;


session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = Validate\deleteResource($_POST);
    } catch (\Exception $e) {
        die();
    }
    
    $model = new Model();
    $model->deleteFile($input['Filename'], $input['Container']);    
    Respond\success();

} else {
    die();
}
