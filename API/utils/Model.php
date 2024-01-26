<?php

require_once('ConnectionSettings.php');
require_once('Respond.php');
require_once('Storage.php');

use storyproducer\Respond;
use storyproducer\Storage;

$lastConnectionTime = 0;

function GetDatabaseConnection() {
	global $lastConnectionTime;
    try {
        // The file ConnectionSettings.php defines some global constants
        // for how to connect to the database. Because php assumes that any
        // variable referenced within a function is locally scoped, the value
        // of $serverName and the other variables is null. To access the
        // global variables, we must use the $GLOBALS associative array.
        $cn = new PDO($GLOBALS['dns'], $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);
//        $cn = new PDO("mysql:host={$GLOBALS['serverName']};dbname={$GLOBALS['databaseName']}",
//            $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);

        $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$lastConnectionTime = time();
        return $cn;
    } catch (\Exception $e) {
        error_log($e);
        return null;
    }
}

function PrepareAndExecute(&$conn, $sql, $params) {
    global $lastConnectionTime;
    $currentTime = time();

    if (($currentTime - $lastConnectionTime)/3600 > 4){
	error_log("new connection dbh");
	$conn = GetDatabaseConnection();
    }
    $lastConnectionTime = $currentTime;
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute($params)) {
	    var_dump($params);
        if (($errors = $conn->errorInfo() ) != null) {
            foreach ($errors as $error) {
                error_log("SQLSTATE: {$error[0]}");
                error_log("code: {$error[1]}");
                error_log("message: {$error[2]}");
            }
        }
        error_log("Unable to execute statement: " . $stmt->queryString);
        throw new DBException();
    }
    return $stmt;
}

function PutFile($directory, $filename, $data) {
    $dir = "{$GLOBALS['filesRoot']}/$directory";
    if (!file_exists($dir) && !is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    error_log("Putting file '$dir/$filename'");
    sleep(3);
    file_put_contents("$dir/$filename", $data);
}

function CopyFileIfExists($from_filename, $to_directory, $to_filename) {
    $to_dir = "{$GLOBALS['filesRoot']}/$to_directory";
    if (!file_exists($to_dir) && !is_dir($to_dir)) {
        mkdir($to_dir, 0777, true);
    }
    $from = "{$GLOBALS['filesRoot']}/$from_filename";
    if (!file_exists($from)) {
        return;
    }
    $to = "{$GLOBALS['filesRoot']}/$to_directory/$to_filename";
    error_log("Copying file '$from' to '$to'");
    copy($from, $to);
}

function GetNumberOfSlides($conn, $storyId) {
    $sql = "SELECT COUNT(*) AS num
		FROM Slide
		WHERE storyId = ?;";
    $stmt = PrepareAndExecute($conn, $sql, array($storyId));
    $model = new Model();
    $numSlides = $model->FetchValueByKey($stmt, 'num');
    if (!$numSlides) {
        Respond\error("Unable to access story approvals");
        throw new DBException();
        ;
    }

    $model->FreeStmt($stmt);
    return $numSlides;
}

function RespondWithError($code, $message) {
    error_log($message);
    // We should not give the end user reasons for why we have internal server
    // errors, but we should log any reason that we have so that we can
    // diagnose the issue.
    http_response_code($code);
    if ($code === 500) {
        echo "Internal server error. Please report this to the server administrator.\n";
    } else {
        echo "$message\n";
    }
    die();
}

class Model {

    private $conn;
    private $storageService;

    public function __construct() {
        $this->conn = Model::GetDatabaseConnection();
        $this->storageService = new Storage\LocalFileStorage('../files');
    }

    protected static function GetDatabaseConnection() {
        try {
            // The file ConnectionSettings.php defines some global constants
          // for how to connect to the database. Because php assumes that any
          // variable referenced within a function is locally scoped, the value
          // of $serverName and the other variables is null. To access the
          // global variables, we must use the $GLOBALS associative array.

            $cn = new PDO($GLOBALS['dns'], $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);

//            $cn = new PDO("mysql:host={$GLOBALS['serverName']};dbname={$GLOBALS['databaseName']}",
//                $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);
            $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            Respond\error("Unable to connect to database");
            error_log($e);
            throw new DBException();
        }
        return $cn;
    }

    private function PrepareStmt($sql, $params) {
        $stmt = $this->conn->prepare($sql);
        for ($i = 0; $i < sizeof($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i]);
        }

        if (!$stmt) {
            Respond\error("Unable to prepare statement: " . $sql);
            throw new DBException();
            ;
        }
        return $stmt;
    }

    public function PrepareAndExecute($sql, $params) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt->execute($params)) {
            if (($errors = $this->conn->errorInfo() ) != null) {
                foreach ($errors as $error) {
                    echo "SQLSTATE: {$error[0]}\n";
                    echo "code: {$error[1]}\n";
                    echo "message: {$error[2]}\n";
                }
            }
            Respond\error("Unable to execute statement: " . $stmt->queryString);
            throw new DBException();
            ;
        }
        return $stmt;
    }

    function FreeStmt(&$stmt) {
        $stmt = null;
        return true;
    }

    public function FetchArray($stmt) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function FetchValueByIndex($stmt, $index) {
        $results = $this->FetchArray($stmt);
        if (!is_array($results)) {
            return false;
        }

        $values = array_values($results);
        if (is_numeric($index) && sizeof($values) > $index) {
            return $values[$index];
        } else if (array_key_exists($index, $results)) {
            return $results[$index];
        }

        return false;
    }

    function FetchValueByKey($stmt, $key) {
        $results = $this->FetchArray($stmt);
        return array_key_exists($key, $results) ? $results[$key] : false;
    }

    private function Query($sql) {
        return $this->PrepareAndExecute($sql, array());
    }

    private function Execute($stmt) {
        return $stmt->execute();
    }

    private function NumRows($stmt) {
        return $stmt->rowCount();
    }

    /* END DB Wrappers */

    public function AddTranslatorMessage($phoneId, $storyTitle, $slideNumber, $message) {
        $this->AddMessage($phoneId, $storyTitle, $slideNumber, $message, 1);
    }

    public function AddConsultantMessage($phoneId, $storyTitle, $slideNumber, $message) {
        $this->AddMessage($phoneId, $storyTitle, $slideNumber, $message, 0);
    }

    private function AddMessage($phoneId, $storyTitle, $slideNumber, $message, $isTranslator) {
        $slideId = $this->GetSlideId($phoneId, $storyTitle, $slideNumber);

        $sql = "INSERT INTO Messages(slideId, message, isTranslator)
				VALUES (?, ?, ?);";

        $stmt = $this->PrepareAndExecute($sql, array($slideId, $message, $isTranslator));
        $this->FreeStmt($stmt);
    }

    public function GetMessages($phoneId, $storyTitle, $slideNumber, $lastId) {
        $slideId = $this->GetSlideId($phoneId, $storyTitle, $slideNumber);

        // We only want to return new messages,
        // so do not return a message whose ID is <= $lastId
        $sql = "SELECT id, message, isTranslator
				FROM Messages
				WHERE slideId = ? AND id > ?
				ORDER BY id ASC;";
        $stmt = $this->PrepareAndExecute($sql, array($slideId, $lastId));

        $messages = array();
        $maxId = -1;
        while ($row = $this->FetchArray($stmt)) {
            $message = array();
            $message['MessageId'] = $row['id'];
            $message['Message'] = $row['message'];
            $message['IsTranslator'] = $row['isTranslator'];
            $messages[] = $message;

            $curId = $row['id'];
            if ($curId > $maxId) {
                $maxId = $curId;
            }
        }
        $this->FreeStmt($stmt);

        $result = array();
        $result['Messages'] = $messages;
        $result['LastId'] = $maxId;
        return $result;
    }

    public function AddSlideLog($phoneId, $templateTitle, $slideNumber, $log) {
        $sql = "UPDATE Approvals
				SET log = ?
				WHERE storyId = ? AND slideNumber = ?;";

        $storyId = $this->GetStoryId($phoneId, $templateTitle);
        $stmt = $this->PrepareAndExecute($sql, array($log, $storyId, $slideNumber));
        $this->FreeStmt($stmt);
    }

    public function AddSlideBacktranslation($phoneId, $templateTitle, $slideNumber, $audioData, $btText) {
        $filename = $phoneId . "/" . $templateTitle . "/" . $slideNumber . ".wav";

        // Place the audio file into blob storage
        $this->AddFileToStorage("backtranslations", $filename, base64_decode($audioData));
        $storyId = $this->GetStoryId($phoneId, $templateTitle);

        // If backtranslation text was provided, add it to the database
        if (!is_null($btText)) {
            $this->AddBtText($storyId, $slideNumber, $btText);
        }
    }

    private function AddBtText($storyId, $slideNumber, $btText) {
        $numberOfSlides = $this->GetNumberOfSlides($this->cn, $storyId);

        // Make sure this story actually contains this slide number
        if ($slideNumber >= $numberOfSlides) {
            Respond\error("Invalid slide number");
            throw new DBException();
            ;
        }

        $sql = "UPDATE Approvals
				SET btText = ?
				WHERE storyId = ? AND slideNumber = ?;";

        $stmt = $this->PrepareStmt($sql, array($btText, $storyId, $slideNumber));
        if (!$this->Execute($stmt)) {
            Respond\erorr("Unable to update backtranslation text of slide #$number in $storyId");
        }
        $this->FreeStmt($stmt);
    }

    public function SaveStory($phoneId, $storyTitle, $slideStatus, $notes) {
        $storyId = $this->GetStoryId($phoneId, $storyTitle);
        $this->UpdateSlideStatuses($storyId, $slideStatus);
        $this->UpdateStoryNotes($storyId, $notes);
    }

    private function UpdateStoryNotes($storyId, $notes) {
        $numberOfSlides = $this->GetNumberOfSlides($this->cn, $storyId);

        if ($numberOfSlides != count($notes)) {
            Respond\error("Invalid number of slide notes");
            throw new DBException();
            ;
        }

        $sql = "UPDATE Approvals
				SET note = ?
				WHERE storyId = ? AND slideNumber = ?;";

        foreach ($notes as $number => $note) {
            $stmt = $this->PrepareStmt($sql, array($note, $storyId, $number));
            if (!$this->Execute($stmt)) {
                Respond\erorr("Unable to update note of slide #$number in $storyId");
            }
            $this->FreeStmt($stmt);
        }
    }

    private function UpdateSlideStatuses($storyId, $slideStatus) {
        $numberOfSlides = $this->GetNumberOfSlides($this->cn, $storyId);

        if ($numberOfSlides != count($slideStatus)) {
            Respond\error("Invalid number of slide statuses");
            throw new DBException();
            ;
        }

        $sql = "UPDATE Approvals
				SET slideStatus = ?
				WHERE storyId = ? AND slideNumber = ?;";

        foreach ($slideStatus as $number => $status) {
            $stmt = $this->PrepareStmt($sql, array($status, $storyId, $number));
            if (!$this->Execute($stmt)) {
                Respond\erorr("Unable to update status of slide #$number in $storyId");
            }
            $this->FreeStmt($stmt);
        }
    }

    private function GetStoryId($phoneId, $storyTitle) {
        $sql = "SELECT id
				FROM Stories
				WHERE projectId = ? AND title = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($phoneId, $storyTitle));

        $storyId = $this->FetchValueByIndex($stmt, 0);
        if (!$storyId) {
            Respond\error("Unable to access story");
            throw new DBException();
            ;
        }

        $this->FreeStmt($stmt);
        return $storyId;
    }

    private function GetSlideId($phoneId, $storyTitle, $slideNumber) {
        $storyId = $this->GetStoryId($phoneId, $storyTitle);

        $sql = "SELECT id
				FROM Approvals
				WHERE storyId = ? AND slideNumber = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($storyId, $slideNumber));

        $slideId = $this->FetchValueByIndex($stmt, 0);
        if (!$slideId) {
            Respond\error("Unable to access story");
            throw new DBException();
            ;
        }

        $this->FreeStmt($stmt);
        return $slideId;
    }

    public function GetSlideStatuses($phoneId, $templateTitle) {
        $storyId = $this->GetStoryId($phoneId, $templateTitle);

        $sql = "SELECT slideNumber, slideStatus
				FROM Approvals
				WHERE storyId = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($storyId));

        $slideStatuses = array();
        while ($row = $this->FetchArray($stmt)) {
            $slideStatuses[$row['slideNumber']] = $row['slideStatus'];
        }
        $this->FreeStmt($stmt);

        $statuses = array();
        $statuses['Statuses'] = $slideStatuses;

        return $statuses;
    }

    public function GetConsultantId($consultantEmail) {
        $sql = "SELECT id
				FROM Consultants
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($consultantEmail));

        $consultantId = $this->FetchValueByIndex($stmt, 0);
        if (!$consultantId) {
            Respond\error("Unable to access consultant email");
            throw new DBException();
            ;
        }

        $this->FreeStmt($stmt);
        return $consultantId;
    }

    public function GetConsultantProjects($consultantId) {
        $sql = "SELECT androidId
				FROM Projects
				WHERE consultantId = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($consultantId));

        $result = array();
        while ($project = $this->FetchArray($stmt)) {
            array_push($result, $project['androidId']);
        }

        $this->FreeStmt($stmt);
        return $result;
    }

    public function ChangePassword($email, $currentPassword, $newPassword) {
        $currentHash = $this->GetPasswordHash($email);
        if (!password_verify($currentPassword, $currentHash)) {
            Respond\error("Incorrect password");
            throw new DBException();
            ;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE Consultants
				SET password = ?
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($newHash, $email));
        $this->FreeStmt($stmt);
    }

    public function GetProjectAndStories($projectId) {
        $sql = "SELECT androidId, language, ethnoCode, country, majorityLanguage, trainerEmail, email, phone, spokenLanguage
				FROM Projects
				WHERE androidId = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($projectId));

        // First get all the project level data
        $result = array();
        while ($project = $this->FetchArray($stmt)) {
            array_push($result, array(
                "Language" => $project['language'],
                "PhoneId" => $project['androidId'],
                "TranslatorEmail" => $project['email'],
                "TranslatorPhone" => $project['phone'],
                "SpokenLanguage" => $project['spokenLanguage'],
                "EthnoCode" => $project['ethnoCode'],
                "Country" => $project['country'],
                "MajorityLanguage" => $project['majorityLanguage'],
                "TrainerEmail" => $project['trainerEmail'],
                "Stories" => array()
                    )
            );
        }
        $this->FreeStmt($stmt);

        // Second, get all the stories under the project
        $sql = "SELECT id, 'eng-NASB' AS defaultVersion, title
				FROM Stories
				WHERE projectId = ?;";

        foreach ($result as &$project) {
            $phoneId = $project['PhoneId'];
            $curStories = $project['Stories'];
            $stmt = $this->PrepareAndExecute($sql, array($phoneId));

            // Loop over every story under this project
            while ($story = $this->FetchArray($stmt)) {
                array_push($project['Stories'], array(
                    "Title" => $story['title'],
                    "StoryId" => $story['id'],
                    "DefaultVersion" => $story['defaultVersion'],
                    "StoryComplete" => false,
                    "SlideStatus" => array(),
                    "Logs" => array(),
                    "Notes" => array()
                        )
                );
            }
            $this->FreeStmt($stmt);
        }

        // Third, get all the slides for each story
        $sql = "SELECT storyId, slideNumber, slideStatus, log, note, btText
				FROM Approvals
				WHERE storyId = ?;";

        foreach ($result as &$project) {
            foreach ($project['Stories'] as &$story) {
                $storyId = $story['StoryId'];
                $stmt = $this->PrepareAndExecute($sql, array($storyId));

                // Loop over every slide in this story, extracting information
                while ($approval = $this->FetchArray($stmt)) {
                    $story['SlideStatus'][$approval['slideNumber']] = $approval['slideStatus'];
                    $story['Logs'][$approval['slideNumber']] = $approval['log'];
                    $story['Notes'][$approval['slideNumber']] = $approval['note'];
                    $story['BacktranslationTexts'][$approval['slideNumber']] = $approval['btText'];
                }

                // Check if all the slides have been approved,
                // meaning remote consultanting will be complete
                $isCompleted = true;
                foreach ($story['SlideStatus'] as $status) {
                    if ($status !== 1) {
                        $isCompleted = false;
                        break;
                    }
                }

                $story['StoryComplete'] = $isCompleted;
                $this->FreeStmt($stmt);
            }
        }

        return $result;
    }

    // Used for debug.php
    public function GetTableNames() {
        $sql = "SELECT table_name 
				FROM information_schema.tables 
				WHERE table_schema = DATABASE();";

        $stmt = $this->Query($sql);
        if (!$stmt) {
            Respond\error("Unable to query table names");
            throw new DBException();
            ;
        }

        $tableNames = array();
        while ($table = $this->FetchArray($stmt)) {
            array_push($tableNames, $table['table_name']);
        }

        $this->FreeStmt($stmt);

        return $tableNames;
    }

    // Also used for debug.php
    public function GetAllRowsFromTable($table) {
        $sql = "SELECT *
				FROM " . $table . ";";

        $stmt = $this->Query($sql);
        if (!$stmt) {
            Respond\error("Unable to prepare get rows from table");
            throw new DBException();
            ;
        }

        $rows = array();
        while ($row = $this->FetchArray($stmt)) {
            array_push($rows, $row);
        }

        $this->FreeStmt($stmt);

        return $rows;
    }

    private function CreateStory($androidId, $templateTitle) {
        // Create the story entry
        $sql = "INSERT INTO Stories(title, projectId)
				VALUES (?, ?);";
        $stmt = $this->PrepareAndExecute($sql, array($templateTitle, $androidId));
        $this->FreeStmt($stmt);

        // get the storyId to return
        $sql = "SELECT id
				FROM Stories
				WHERE projectId = ? AND title = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($androidId, $templateTitle));

        $storyId = $this->FetchValueByIndex($stmt, 0);
        if (!$storyId) {
            Respond\error("No story found");
            throw new DBException();
            ;
        }

        $this->FreeStmt($stmt);

        return $storyId;
    }

    public function AddReviewRequest($androidId, $templateTitle, $numberOfSlides) {
        $storyId = $this->CreateStory($androidId, $templateTitle);

        // Create all the slides in the database for this story
        $sql = "INSERT INTO Approvals(storyId, slideNumber, slideStatus)
				VALUES (?, ?, 0);";

        $slideNumber = 0;

        for ($i = 0; $i <= $numberOfSlides; $i++) {
            $slideNumber = $i;
            $stmt = $this->PrepareStmt($sql, array($storyId, $slideNumber));

            if (!$this->Execute($stmt)) {
                Respond\error("Unable to add approval");
                throw new DBException();
                ;
            }
            $filename = $androidId . "/" . $templateTitle . "/" . $slideNumber . ".wav";
            $silentAudio = file_get_contents(dirname(__FILE__) . '/silent.wav');
            $this->AddFileToStorage("backtranslations", $filename, $silentAudio);
        }

        $this->FreeStmt($stmt);
    }

    function AddConsultant($name, $language, $phone, $email, $password, $isadmin) {
        if ($this->isAlreadyConsultant($email)) {
            Respond\error("Consultant email already exists");
            throw new DBException();
        }
        $sql = "INSERT INTO Consultants(name, language, phone, email, password, isAdmin)
				VALUES(?, ?, ?, ?, ?, ?);";
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->PrepareAndExecute($sql, array($name, $language, $phone, $email, $passwordHash, $isadmin));
        $this->FreeStmt($stmt);
    }

    function RemoveAssignment($conId, $projId) {
        $sql = "DELETE FROM Assigned
                WHERE ConsultantId = ? AND
                      ProjectId = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($conId, $projId));
        $this->FreeStmt($stmt);
    }

    function RemoveConsultant($email) {
        $sql = "DELETE FROM Consultants WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));
        $this->FreeStmt($stmt);
    }

    function CleanConsultant($ConsultantId) {
        $sql = "DELETE FROM Assigned WHERE ConsultantId = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($ConsultantId));
        $this->FreeStmt($stmt);

	// get list of androidIds before we delete all the info
	$projectIds = $model->GetConsultantProjects($ConsultantId);
	$this->SQLCleanup(2);
        $projDir = "{$GLOBALS['filesRoot']}/Projects/";
        foreach ($projectIds as &$androidId) {
            $this->recursiveDelete($projDir . $androidId);
	}
    }

    function RemoveProject($projectId) {
       $androidId = $this->GetAndroidId($projectId);

       $sql = "DELETE FROM Assigned WHERE projectId = ?;";
       $stmt = $this->PrepareAndExecute($sql, array($projectId));
       $stmt = null;
   
	   $this->SQLCleanup(2);
        $projDir = "{$GLOBALS['filesRoot']}/Projects/";
        $this->recursiveDelete($projDir . $androidId);
    }

    function GetAndroidId($projectId)
    {
        $sql = "SELECT androidId FROM Projects WHERE id = ?";
        $stmt = $this->PrepareAndExecute($sql, array($projectId));

        $result = array();
        if ($project = $this->FetchArray($stmt)) {
            $this->FreeStmt($stmt);
            return $project['androidId'];
        }
        return "error"; // this is used as a folder, don't return empty or recursive delete will do more than you want
    }

    // called after deleting/cleaning a consultant, project (phone) or story.  Get rid of any unlinked rows
    function SQLCleanup($level) {
//	$sql = "";
// Do these no matter what - if the ID doesn't exist clean it...    
//	if ($level == 1)
	    $sql = "delete FROM Assigned WHERE ConsultantId not in (select id from Consultants);";
        $sql = $sql . "delete FROM Assigned WHERE projectId not in (select id from Projects);";
//	if ($level <= 2)
	    $sql = $sql . "delete FROM Projects WHERE id not in (select Distinct ProjectId from Assigned);";
//	if ($level <= 3)
	    $sql = $sql . "delete FROM Stories WHERE projectId not in (select id from Projects);";
	    $sql = $sql . "delete FROM Slide WHERE storyId not in (select id from Stories);";
	    $sql = $sql . "delete FROM Messages WHERE storyId not in (select id from Stories);";

        $stmt = $this->PrepareAndExecute($sql, array());
        $this->FreeStmt($stmt);
    }

    function recursiveDelete($dir)
    {
	foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                if ($fileInfo->isDir()) {
                    $this->recursiveDelete($fileInfo->getPathname());
                } else {
                    unlink($fileInfo->getPathname());
                }
            }
	}
	rmdir($dir);
    }

    // Check if an email address is already registered to an existing consultant
    function IsAlreadyConsultant($email) {
        $sql = "SELECT *
				FROM Consultants
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));
        if (!$stmt) {
            Respond\error("Unable to prepare statement");
            throw new DBException();
            
        } else if (!$this->Execute($stmt)) {
            Respond\error("Unable to execute statement:" . $stmt->queryString);
            throw new DBException();
            
        }
        $numRows = $this->NumRows($stmt);
        $this->FreeStmt($stmt);
        return $numRows !== 0;
    }

    // Get password hash for a specific email address
    function GetPasswordHash($email) {
        $sql = "SELECT password
				FROM Consultants
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));
        $passwordHash = $this->FetchValueByIndex($stmt, 0) ?: '';
        $this->FreeStmt($stmt);
        return $passwordHash;
    }

    // Set the password for a specific consultant
    function SetPassword($email, $password) {
        $sql = "UPDATE Consultants
				SET password = ?
				WHERE email = ?;";
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->PrepareAndExecute($sql, array($newHash, $email));
        $this->FreeStmt($stmt);
    }

    // Check if the email address belongs to an admin
    function IsAdmin($email) {
        $sql = "SELECT isAdmin
				FROM Consultants
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));
        $isAdmin = boolval($this->FetchValueByIndex($stmt, 0) ?: false);
        $this->FreeStmt($stmt);
        return $isAdmin;
    }

    function MakeAdmin($email) {
        $sql = "UPDATE Consultants
				SET isAdmin = 1
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));

        $this->FreeStmt($stmt);
    }

    function RemoveAdmin($email) {
        $sql = "UPDATE Consultants
				SET isAdmin = 0
				WHERE email = ?;";
        $stmt = $this->PrepareAndExecute($sql, array($email));

        $this->FreeStmt($stmt);
    }

}

class DBException extends \Exception {

    public function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
