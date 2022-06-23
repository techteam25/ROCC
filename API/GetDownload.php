<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $input = Validate\getDownload($_GET);
    } catch (\Exception $e) {
        die();
    }
    $model = new Model();
    $file = $input['File'];
    $container = $input['Container'];
    $fileStream = $model->DownloadFile($file, $container);
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename\"' . $file .'\"');
    fpassthru($fileStream);
} else {
    http_response_code(405);
    die();
}
