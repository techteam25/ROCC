<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\loginRequest($_POST);
	} catch(\Exception $e) {
		die();
	}
	$email    = $input['Email'];
	$password = $input['Password'];


	$model = new Model();
	$hash  = $model->GetPasswordHash($email);

	$model = new Model();
	$hash  = $model->GetPasswordHash($email);
	
	if (password_verify($password, $hash)) {
		$isLoginSuccess    = true;
		$_SESSION['email'] = $email;

		$_SESSION['admin'] = $model->IsAdmin($email) == 1;
	} else {
		$isLoginSuccess = false;
	}

	Respond\successData(array("LoginSuccess"=>$isLoginSuccess));

} else {
	// not a POST request
	http_response_code(405);
	die();
}
