<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $input = Validate\uploadZip($_POST);
    } catch(\Exception $e) {
        die();
    }
    $directory = $input['Directory'];
    $container = $input['Container'];
    
    $model = new Model();
    for ($i = 0; $i < sizeof($_FILES['file']['tmp_name']); $i++) {
        $zip = new ZipArchive();
        $zip->open($_FILES['file']['tmp_name'][$i]);

        for($j = 0; $j < $zip->numFiles; $j++) {
            $name = $zip->getNameIndex($j);
            $stream = $zip->getStream($name);
            $fileData = '';
            while (!feof($stream)) {
                $fileData .= fread($stream, 2);
            }
            fclose($stream);
            $model->AddFileToStorage($container, $directory 
                . $name, $fileData);
        }
    }
    header('Location: ../template_manager.php');
} else {
    // not a POST request
    http_response_code(405);
    die();
}
