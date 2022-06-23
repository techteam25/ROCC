<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;
 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
	try {
		$input = Validate\RequestRemoteReview($_POST);
	} catch(\Exception $e) {
		die();
	}
	$phoneId        = $input['PhoneId'];
	$templateTitle  = $input['TemplateTitle'];
	$numberOfSlides = $input['NumberOfSlides'];

	$model = new Model();

	// Create new translator entry
	$model->AddReviewRequest($phoneId, $templateTitle, $numberOfSlides);

	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}