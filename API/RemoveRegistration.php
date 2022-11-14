<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\removeRegistration($_POST);
	} catch(\Exception $e) {
		error_log("RemoveRegistration: Not valid");
		die();
	}
	$id = $input['id'];
	$model = new Model();

	$model->RemoveProject($id);
	//Respond\success();
    header("Location: ../admin.php");
    

} else {
	// not a POST request
	error_log("RemoveRegistration: Not a POST request");
	http_response_code(405);
	die();
}
