<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = Validate\deleteDirectory($_POST);
    } catch (\Exception $e) {
        die();
    }
    $directory = $input['Directory'];
    $container = $input['Container'];
    $model = new Model();
    $dirFiles = $model->ListContainerContents($container, $directory);
    foreach($dirFiles as $file){
        $model->DeleteFile($file, $container);        
    }
    Respond\success();
} else {
    die();
}
