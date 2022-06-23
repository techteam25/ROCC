<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = Validate\moveDirectory($_POST);
    } catch (\Exception $e) {
        die();
    } 
    $container = $input['Container'];
    $sourceDir = $input['SourceDirName'];
    $destDir = $input['DestDirName'];
    $model = new Model();
    $resourcesToMove = $model->ListContainerContents($container, $sourceDir); 
    $newResourceNames = array();
    foreach($resourcesToMove as $sourceResourceName) {
        $destResourceName = $destDir . ltrim($sourceResourceName, $sourceDir);
        $model->CopyFile($sourceResourceName, $destResourceName, $container);
        $model->DeleteFile($sourceResourceName, $container);
        $newResourceNames[] = $destResourceName;
    }
    Respond\successData($newResourceNames);

} else {
    die();
}
