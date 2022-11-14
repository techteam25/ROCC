<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\removeConsultant($_POST);
	} catch(\Exception $e) {
		die();
	}
	$id = $input['id'];
	$model = new Model();

	$model->CleanConsultant($id);
	//Respond\success();
    header("Location: ../admin.php");
    

} else {
	// not a POST request
	http_response_code(405);
	die();
}
