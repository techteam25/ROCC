<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\getStories($_POST);
	} catch(\Exception $e) {
		die();
	}

	$model = new Model();

	$consultantId = $model->GetConsultantId($_SESSION['email']);
	$projectIds = $model->GetConsultantProjects($consultantId);

	$projectsAndStories = array();
	$projectsAndStories['projects'] = array();
	foreach ($projectIds as $projectId) {
		array_push($projectsAndStories['projects'], $model->GetProjectAndStories($projectId));
	}

	Respond\successData($projectsAndStories);

} else {
	// not a GET request
	http_response_code(405);
	die();
}