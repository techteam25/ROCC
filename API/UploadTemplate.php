<?php
require_once('utils/Model.php');
require_once('utils/Respond.php');
require_once('utils/Validate.php');
use storyproducer\Respond;
use storyproducer\Validate;

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	try {
		$input = Validate\uploadTemplate($_POST);
	} catch(\Exception $e) {
		die();
	}
	$language      = $input['Language'];
    $templateTitle = $input['TemplateTitle'];
    $templateDirectory = $language.'/'.$templateTitle.'/';

    $model = new Model();

    for ($i = 0; $i < sizeof($_FILES['file']['tmp_name']); $i++) {

        $myfile   = fopen($_FILES['file']['tmp_name'][$i], "r") or die("Unable to open file!");
        $fileData = fread($myfile, filesize($_FILES['file']['tmp_name'][$i]));
        fclose($myfile);
	    
	/*$old = ["\xC2\xAB", "\xC2\xBB", "\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9A", "\xE2\x80\x9B", 
                "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9E", "\xE2\x80\x9F", "\xE2\x80\xB9", "\xE2\x80\xBA",
                "\xE2\x80\x93", "\xE2\x80\x94", "\xE2\x80\xA6", '”', '“'
    	];

    	$utf8 = ["<<", ">>", "'", "'", "'", "'", '"', '"', '"', '"', "<",">", "-", "-", "...", '"', '"'];
	    
	$fileData = str_replace($old, $utf8, $fileData);*/

        $model->AddFileToStorage('templates', $templateDirectory, $_FILES['file']['name'][$i], $fileData);
    }

    // To send HTML mail, the Content-type header must be set
    // $headers[] = 'MIME-Version: 1.0';
    // $headers[] = 'Content-type: text/html; charset=iso-8859-1';

    // // Additional headers
    // $headers[] = 'To: Blake Lasky <blasky@cedarville.edu>';
    // $headers[] = 'From: ROCC <notification@storyproducer.eastus.cloudapp.azure.com>';
	// mail('blasky@cedarville.edu', 'TEST', 'msg', implode("\r\n", $headers));
	Respond\success();
} else {
	// not a POST request
	http_response_code(405);
	die();
}
