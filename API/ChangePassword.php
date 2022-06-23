<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\changePassword($_POST);
	} catch(\Exception $e) {
		die();
	}
	$currentPassword = $input['CurrentPassword'];
	$newPassword     = $input['NewPassword'];

	$model = new Model();
	$model->ChangePassword($_SESSION['email'], $currentPassword, $newPassword);

	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}