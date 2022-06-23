<?php
namespace storyproducer\Respond;

// This class is designed for the endpoints to use to respond to the API calls

// Return an error
// $error is the message to be returned
function error($error) {
	output(false, $error, NULL);
}

// Return success and data
function successData($data) {
	output(true, NULL, $data);
}

// Return success without any extra data
function success() {
	successData(NULL);
}

// This will actually do the return
function output($success, $error, $data) {
	$jsonArray = array(
		"Success" => $success,
		"Error"   => $error
	);

	// If $data has data, add it to the returned data
	if (is_array($data)) {
		$jsonArray = array_merge($jsonArray, $data);
	}
	
	if(!headers_sent()) {
		header('Content-type: application/json');
	}

	// Return the status and data as JSON
	echo json_encode($jsonArray);
}