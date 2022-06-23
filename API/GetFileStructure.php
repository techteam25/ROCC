<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
	$input = Validate\getFileStructure($_POST);
    } catch (\Exception $e) {
    	die();
    }
    $container = $input['Container'];

    $model = new Model();
    $contents = [];    
    $contents[] = $model->ListContainerContents($container);
    Respond\successData($contents);

} else {
    http_response_code(405);
    die();
}

