<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\addConsultant($_POST);
	} catch(\Exception $e) {
		die();
	}
	$name     = $input['Name'];
	$language = $input['Language'];
	$phone    = $input['Phone'];
	$email    = $input['Email'];
	$password = $input['Password'];
	$isadmin  = $input['IsAdmin'];


	$model = new Model();
	$model->AddConsultant($name, $language, $phone, $email, $password, $isadmin);

	//Respond\success();
    header("Location: ../admin.php");

} else {
	// not a POST request
	http_response_code(405);
	die();
}
