<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$input = Validate\getResource($_POST);
	} catch(\Exception $e) {
		die();
	}
	
	$uri = explode('/', str_replace('%20', ' ', $input['URI']));
	if(sizeof($uri) < 3) {
		Respond\error("Improper uri: " . $input['URI']);
		die();
	}

	$container = $uri[1];
	$uri = array_splice($uri, 2);
	
	$path = '';
	foreach($uri as $part) {
		$path .= '/' . $part;
	}
	$path = substr($path, 1);
	
	if(empty($container) || empty($path)) {
		Respond\error("Improper container name or path: " . $input['URI']);
		die();
	}

	$model = new Model();
	$response = $model->GetFileFromStorage($container, $path);
	$response = iconv("Windows-1251", "UTF-8//TRANSLIT", $response);
	Respond\successData(array("Data" => $response));

} else {
	// not a GET request
	http_response_code(405);
	die();
}