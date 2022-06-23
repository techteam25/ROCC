<?php
require_once('utils/ConnectionSettings.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\getContainerName($_POST);
	} catch(\Exception $e) {
		die();
    }
    $container_type = $input['ContainerType'];
    $containerName['Container'] = getContainerName($container_type);
    Respond\successData($containerName);

} else {
    die();
}


