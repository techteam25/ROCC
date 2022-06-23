<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\saveStory($_POST);
	} catch(\Exception $e) {
		die();
	}
	$phoneId        = $input['PhoneId'];
	$storyTitle     = $input['StoryTitle'];
	$slideStatus    = $input['SlideStatus'];
	$notes          = $input['Notes'];

	$model = new Model();
	$model->SaveStory($phoneId, $storyTitle, $slideStatus, $notes);

	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}