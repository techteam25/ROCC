<?php
require_once('utils/ConnectionSettings.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\getBiblePassage($_POST);
	} catch(\Exception $e) {
		die();
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://bibles.org/v2/' . $input['Version'] . '/passages.js?q[]=' . $input['Query']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, getBibleConnectionKey() . ':X');
	$response = curl_exec($ch);
	curl_close($ch);

	$response = json_decode($response);
	Respond\successData(array('Passage' => $response->response->search->result->passages[0]));
} else {
	// not a GET request
	http_response_code(405);
	die();
}