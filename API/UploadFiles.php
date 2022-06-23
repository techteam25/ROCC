<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $input = Validate\uploadFiles($_POST);
    } catch(\Exception $e) {
        die();
    }
    $directory = $input['Directory'];
    $container = $input['Container'];
    
    $model = new Model();
    for ($i = 0; $i < sizeof($_FILES['file']['tmp_name']); $i++) {

        $file   = fopen($_FILES['file']['tmp_name'][$i], "r") 
            or die("Unable to open file!");
        $fileData = fread($file, filesize($_FILES['file']['tmp_name'][$i]));
        fclose($file);
        $model->AddFileToStorage($container, $directory 
                . $_FILES['file']['name'][$i], $fileData);
    }
    header('Location: ../template_manager.php');
} else {
    // not a POST request
    http_response_code(405);
    die();
}
