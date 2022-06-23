<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\setPassword($_POST);
	} catch(\Exception $e) {
		die();
	}
	$email    = $input['Email'];
	$password = $input['Password'];

	$model = new Model();
	$isAdmin = $model->IsAdmin($_SESSION['email']);
	if (!$isAdmin) {
		Respond\error("User is not admin");
		die();
	}

	$model->SetPassword($email, $password);
	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}