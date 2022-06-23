<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = Validate\createDirectory($_POST);
    } catch (\Exception $e) {
        die();
    }
    $container = $input['Container'];
    $dir = $input['Directory'] . '/';

    $contents = 'This is a placeholder file. It only exists because of' .
        ' Azure Blob Storage\'s flat directory structure';

    $model = new Model();
    $model->AddFileToStorage($container, $dir, $contents);
    Respond\success();
} else {
    die();
}

