<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\getAdminStatus($_POST);
	} catch(\Exception $e) {
		die();
	}
	$email    = $input['Email'];


	$model = new Model();
	$isAdmin = $model->IsAdmin($_SESSION['email']);
	if (!$isAdmin) {
		Respond\error("User is not admin");
		die();
	}

	$adminStatus = $model->IsAdmin($email);
	Respond\successData(array("AdminStatus"=>$adminStatus));

} else {
	// not a POST request
	http_response_code(405);
	die();
}