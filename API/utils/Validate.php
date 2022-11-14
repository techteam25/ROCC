<?php
namespace storyproducer\Validate;
require_once('Respond.php');
use storyproducer\Respond;

// This class contains methods for validating the arguments
// passed to an endpoint. Each endpoint has its own method.

function registerPhone($input) {
	ensureKey(access($input, 'Key'));

	$phoneId                 = access($input, 'PhoneId');
	$translatorEmail         = access($input, 'TranslatorEmail');
	$translatorPhone         = access($input, 'TranslatorPhone');
	$translatorLanguage      = access($input, 'TranslatorLanguage');
	$projectEthnoCode        = access($input, 'ProjectEthnoCode');
	$projectLanguage         = access($input, 'ProjectLanguage');
	$projectCountry          = access($input, 'ProjectCountry');
	$projectMajorityLanguage = access($input, 'ProjectMajorityLanguage');
	$consultantEmail         = access($input, 'ConsultantEmail');
	$trainerEmail            = access($input, 'TrainerEmail');

	$result = array();
	$result['PhoneId']                 = phoneId($phoneId);
	$result['TranslatorEmail']         = email($translatorEmail);
	$result['TranslatorPhone']         = phone($translatorPhone);
	$result['TranslatorLanguage']      = language($translatorLanguage);
	$result['ProjectEthnoCode']        = ethno($projectEthnoCode);
	$result['ProjectLanguage']         = language($projectLanguage);
	$result['ProjectCountry']          = country($projectCountry);
	$result['ProjectMajorityLanguage'] = language($projectMajorityLanguage);
	$result['ConsultantEmail']         = email($consultantEmail);
	$result['TrainerEmail']            = email($trainerEmail);

	return $result;
}

function RequestRemoteReview($input) {
	ensureKey(access($input, 'Key'));
	
	$result = array();
	$result['PhoneId']        =         phoneId(access($input, 'PhoneId'));
	$result['TemplateTitle']  =             str(access($input, 'TemplateTitle'));
	$result['NumberOfSlides'] =  positiveNumber(access($input, 'NumberOfSlides'));

	return $result;
}

function getStories($input) {
	ensureLoggedIn();
}

function getBiblePassage($input) {
	ensureLoggedIn();
	$result = array();
	$result['Query']   = str(urlencode(access($input, 'Query')));
	$result['Version'] =           str(access($input, 'Version'));

	return $result;
}

function getBibleVersions($input) {
	ensureLoggedIn();
}

function getResource($input) {
	ensureLoggedIn();
	$result = array();
	$result['URI'] = str(access($input, 'URI'));

	return $result;
}

function addConsultant($input) {
	ensureLoggedIn();

	$result = array();
	$result['Name']     =              str(access($input, 'Name'));
	$result['Language'] =              str(access($input, 'Language'));
	$result['Phone']    =            phone(access($input, 'Phone'));
	$result['Email']    =            email(access($input, 'Email'));
	$result['Password'] =         password(access($input, 'Password'));
	//$result['IsAdmin']  = (int) filter_var(access($input, 'IsAdmin'), FILTER_VALIDATE_BOOLEAN);
	$result['IsAdmin']  = (int) filter_var($input, FILTER_VALIDATE_BOOLEAN);
	return $result;
}

function loginRequest($input) {
	$result = array();
	$result['Email']    = email(access($input, 'Email'));
	$result['Password'] =       access($input, 'Password');

	return $result;
}

function uploadTemplate($input) {
	ensureAdmin();
	$result = array();
	$result['Language'] = strtolower(str(access($input, 'Language')));
	$result['TemplateTitle'] = access($input, 'TemplateTitle');

	return $result;
}

function uploadSlideBacktranslation($input) {
	ensureKey(access($input, 'Key'));
	
	$result = array();
	$result['PhoneId']       = phoneId(access($input, 'PhoneId'));
	$result['TemplateTitle'] =     str(access($input, 'TemplateTitle'));
	$result['SlideNumber']   =  number(access($input, 'SlideNumber'));
	$result['Data']          =  base64(access($input, 'Data'));
	if (array_key_exists('BacktranslationText', $input)) {
		$result['BacktranslationText'] =  access($input, 'BacktranslationText');
	} else {
		$result['BacktranslationText'] =  '';
	}

	return $result;
}

function changePassword($input) {
	ensureLoggedIn();

	$result = array();
	$result['CurrentPassword'] = access($input, 'CurrentPassword');
	$result['NewPassword']     = password(access($input, 'NewPassword'));
	return $result;
}

function saveStory($input) {
	ensureLoggedIn();

	$result = array();
	$result['PhoneId']        = phoneId(access($input, 'PhoneId'));
	$result['StoryTitle']     =         access($input, 'StoryTitle');
	$result['Notes']          =         access($input, 'Notes');

	$result['SlideStatus'] = array();
	if (!is_array(access($input, 'SlideStatus'))) {
		Respond\error("Slide statuses are not an array");
		throw new InputException();
	}
	foreach (access($input, 'SlideStatus') as $status) {
		array_push($result['SlideStatus'], slideStatus($status));
	}

	if(!is_array(access($input, 'Notes'))) {
		Respond\error("Notes is not an array");
		throw new InputException();
	}

	return $result;
}

function getSlideStatuses($input) {
	ensureKey(access($input, 'Key'));

	$result = array();
	$result['PhoneId']       = phoneId(access($input, 'PhoneId'));
	$result['TemplateTitle'] =         access($input, 'TemplateTitle');

	return $result;
}

function getMessagesMobile($input) {
	ensureKey(access($input, 'Key'));

	$result = array();
	$result['PhoneId']     = phoneId(access($input, 'PhoneId'));
	$result['StoryTitle']  =     str(access($input, 'StoryTitle'));
	$result['SlideNumber'] =  number(access($input, 'SlideNumber'));
	$result['LastId']      =  number(access($input, 'LastId'));

	return $result;
}

function getMessagesWeb($input) {
	ensureLoggedIn();

	$result = array();
	$result['PhoneId']     = phoneId(access($input, 'PhoneId'));
	$result['StoryTitle']  =     str(access($input, 'StoryTitle'));
	$result['SlideNumber'] =  number(access($input, 'SlideNumber'));
	$result['LastId']      =  number(access($input, 'LastId'));

	return $result;
}

function sendMessageMobile($input) {
	ensureKey(access($input, 'Key'));

	$result = array();
	$result['PhoneId']     = phoneId(access($input, 'PhoneId'));
	$result['StoryTitle']  =     str(access($input, 'StoryTitle'));
	$result['SlideNumber'] =  number(access($input, 'SlideNumber'));
	$result['Message']     =     str(access($input, 'Message'));

	return $result;
}

function sendMessageWeb($input) {
	ensureLoggedIn();

	$result = array();
	$result['PhoneId']     = phoneId(access($input, 'PhoneId'));
	$result['StoryTitle']  =     str(access($input, 'StoryTitle'));
	$result['SlideNumber'] =  number(access($input, 'SlideNumber'));
	$result['Message']     =     str(access($input, 'Message'));

	return $result;
}

function manageFiles() {
	ensureLoggedIn();
}

function makeAdmin($input) {
	ensureAdmin();

	$result = array();
	$result['Email'] = email(access($input, 'Email'));

	return $result;
}

function removeAdmin($input) {
	ensureAdmin();

	$result = array();
	$result['Email'] = email(access($input, 'Email'));

	return $result;
}

function getAdminStatus($input) {
	ensureLoggedIn();

	$result = array();
	$result['Email'] = email(access($input, 'Email'));
	
	return $result;
}

function removeConsultant($input) {
	ensureAdmin();

	$result = array();
	$result['Email'] = email(access($input, 'Email'));
	
	return $result;
}


function removeRegistration($input) {
	ensureAdmin();

	$result = array();
	$result['id'] = number(access($input, 'id'));

	return $result;
}

function setPassword($input) {
	ensureLoggedIn();

	$result = array();
	$result['Email']    =    email(access($input, 'Email'));
	$result['Password'] = password(access($input, 'Password'));

	return $result;
}

function getFileStructure($input) {
	ensureAdmin();
	$result = array();
	$result['Container'] = str(access($input, 'Container'));

	return $result;
}

function deleteResource($input) {
    ensureAdmin();
    $result = array();
    $result['Filename'] = str(access($input, 'Filename'));
    $result['Container'] = str(access($input, 'Container'));
        
    return $result;
}

function deleteDirectory($input) {
    ensureAdmin();
    $result = array();
    $result['Directory'] = str(access($input, 'Directory'));
    $result['Container'] = str(access($input, 'Container'));
        
    return $result;
}

function moveDirectory($input) {
    ensureAdmin();
    $result = array();
    $result['Container'] = str(access($input, 'Container'));
    $result['SourceDirName'] = str(access($input, 'SourceDirName'));
    $result['DestDirName'] = str(access($input, 'DestDirName'));
    
    return $result;
}

function moveResource($input) {
    ensureAdmin();
    $result = array();
    $result['Container'] = str(access($input, 'Container'));
    $result['SourceFilename'] = str(access($input, 'SourceFilename'));
    $result['DestFilename'] = str(access($input, 'DestFilename'));

    return $result;
}

function getDownload($input) {
    ensureAdmin();
    $result = array();
    $result['Container'] = str(access($input, 'Container'));
    $result['File'] = str(access($input, 'File'));
    
    return $result;
}

function createDirectory($input) {
    ensureAdmin();
    $result = array();
    $result['Container'] = str(access($input, 'Container'));
    $result['Directory'] = str(access($input, 'Directory'));

    return $result;
}

function uploadFiles($input) {
    ensureAdmin();
    $result = array();
    $result['Container'] = str(access($input, 'Container'));
    $result['Directory'] = str(access($input, 'Directory'));
    
    return $result;
}

function uploadZip($input) {
	ensureAdmin();
	$result = array();
	$result['Container'] = str(access($input, 'Container'));
	$result['Directory'] = str(access($input, 'Directory'));
    
	return $result;
}

function getContainerName($input) {
	ensureLoggedIn();
	$result = array();
	$result['ContainerType'] = containerTypes($input['ContainerType']);

	return $result;
} 

// ********
// Methods for internal use
// ********
function positiveNumber($num) {
	$result = number($num);
	if ($result <= 0) {
		Respond\error("Number must be positive");
		throw new InputException();
	}
	return $result;
}

// Make sure mobile calls provide the correct Key
function ensureKey($key) {
	if (!hash_equals("XUKYjBHCsD6OVla8dYAt298D9zkaKSqd", $key)) {
		Respond\error("Invalid key");
		throw new InputException();
	}
}

// Make sure current user is an admin
function ensureAdmin() {
	//ensureLoggedIn();
	if($_SESSION['admin'] !== true) {
		Respond\error("Not admin");
		throw new InputException();
	}
}

// Make sure a consultant is logged in to perform these actions
function ensureLoggedIn() {
	if (!isset($_SESSION['email'])) {
		
		Respond\error("No user logged in");
		throw new InputException();
	}
}

// Slide statuses can only be -1, 0, or 1
function slideStatus($status) {
	$result = number($status);
	switch ($result) {
		case -1:
		case 0:
		case 1:
			break;
		default:
			Respond\error("Invalid slide status $result");
			throw new InputException();
	}
	return $result;
}

function base64($data) {
	$result = $data;
	if (!base64_decode($data, true)) {
		Respond\error("Invalid Base64 string");
		throw new InputException();
	}
	return $result;
}

// Passwords must be 8-64 characters long
function password($pass) {
	$result = $pass;
	if (strlen($result) < 8 || 64 < strlen($result)) {
		Respond\error("Password is not 8-64 characters");
		throw new InputException();
	}
	return $result;
}

function number($num) {
	$result = trim($num);
	if (!is_numeric($result)) {
		Respond\error("Consultant ID is not a number");
		throw new InputException();
	}
	return $result;
}

function str($str) {
	$result = trim($str);
	$result = htmlspecialchars($result);
	return $result;
}

function checkNullOrEmpty($input) {
	if (empty($input)) {
		Respond\error("Input field is empty");
		throw new InputException();
	}
}

// Check for valid 16 digit hex string which is Android GUID
function phoneId($id) {
	$result = trim($id);
	$result = strtolower($result);
	$isValid = preg_match('/^[a-f0-9]{16}$/', $result);
	if (!$isValid) {
		Respond\error("Invalid Android id");
		throw new InputException();
	}
	checkNullOrEmpty($result);
	return $result;
}

// Use PHP to check if email address is valid
function email($email) {
	$result = trim($email);
	$result = strtolower($result);
	if (filter_var($result, FILTER_VALIDATE_EMAIL) == false) {
		Respond\error("Invalid email address: " . $result);
		throw new InputException();
	}
	checkNullOrEmpty($result);
	return $result;
}

// Remove any non-numeric characters
// and ensure it is only 10 digits
// Do not check length - user may not be in USA
function phone($phone) {
	$result = trim($phone);
	//$result = preg_replace('/[^0-9]/', '', $result);
	//$isValid = preg_match('/^[0-9]{10}$/', $result);
	//if (!$isValid) {
		//Respond\error("Invalid phone number");
		//throw new InputException();
	//}
	checkNullOrEmpty($result);
	return $result;
}

// Validate a language
function language($language) {
	$result = trim($language);
	checkNullOrEmpty($result);
	return $result;
}

// Validate a coutry name
function country($country) {
	$result = trim($country);
	checkNullOrEmpty($result);
	return $result;
}

// Validate a three letter ethno code
function ethno($ethno) {
	$result = trim($ethno);
	$result = strtolower($result);
	$result = preg_replace('/[^a-z]/', '', $result);
	$isValid = preg_match('/^[a-z]{3}$/', $result);
	if (!$isValid) {
		Respond\error("Invalid ethnologue code");
		throw new Exception;
	}
	checkNullOrEmpty($result);
	return $result;
}

// Check if the key exists in the array before accessing it
function access($array, $key) {
	if(!array_key_exists($key, $array)) {
		Respond\error("Improper input sent");
		throw new InputException();
	}

	return $array[$key];
}

// Ensure that only the container name for the backtranslations or templates containers have been requested
function containerTypes($containerType) {
	if($containerType === 'templates') {
		return 'templates';
	} else if ($containerType === 'backtranslations') {
		return 'backtranslations';
	} else {
		Respond\error("Improper input sent");
		throw new InputException();
	}
}

class InputException extends \Exception {
    public function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}

// Make uncaught exceptions print nothing
function nocall(\Exception $e) {
}
//set_exception_handler('storyproducer\Validate\nocall');
