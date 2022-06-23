<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\removeAdmin($_POST);
	} catch(\Exception $e) {
		die();
	}
	$email = $input['Email'];

	$model = new Model();

	$model->RemoveAdmin($email);
	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}