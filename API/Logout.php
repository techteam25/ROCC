<?php
require_once('utils/Respond.php');
use storyproducer\Respond;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
	session_destroy();
	Respond\success();

} else {
	// not a POST request
	http_response_code(405);
	die();
}