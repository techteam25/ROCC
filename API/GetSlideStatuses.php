<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;
 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\getSlideStatuses($_POST);
	} catch(\Exception $e) {
		die();
	}
	$phoneId        = $input['PhoneId'];
	$templateTitle  = $input['TemplateTitle'];

	$model = new Model();

	// Create new translator entry
	$data = array();
	$statuses = $model->GetSlideStatuses($phoneId, $templateTitle);
	$data['Status']  = $statuses['Statuses'];

	Respond\successData($data);

} else {
	// not a POST request
	http_response_code(405);
	die();
}