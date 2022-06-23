<?php
require_once('utils/Respond.php');
use storyproducer\Respond;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_SESSION['email'])) {
		Respond\successData(array('Email' => $_SESSION['email']));
	} else {
		Respond\error('No user logged in');
		die();
	}

} else {
	// not a POST request
	http_response_code(405);
	die();
}