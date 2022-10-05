<?php

require_once('utils/Model.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $language = $_POST['language'];
    $conn = GetDatabaseConnection();
    //Added selected fields to GROUPBY Lucas
    $sql = 'SELECT ifnull(approved, 999) as approved, Projects.androidId AS currProjId, title, S.language AS language, storyId FROM Projects
	    INNER JOIN (SELECT DISTINCT (SUM(CASE WHEN Slide.isApproved = 1 THEN 1 ELSE 0 END) / count(Slide.isApproved)) AS approved, Stories.title as title, Stories.id as storyId, Stories.projectId AS PID, Stories.language as language from Stories 
	    LEFT JOIN Slide
	    on Slide.storyId = Stories.id
	    GROUP BY Stories.title, Stories.id, Stories.projectId) S
            ON Projects.id = S.PID 
        WHERE Projects.language = ?
	    ORDER BY approved ASC';
    $stmt = PrepareAndExecute($conn, $sql, array($language));
    $data_arr = []; 

    if ($stmt->rowCount() > 0) {
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            array_push($data_arr, array("title" => $row['title'], "currProjId" => $row['currProjId'], "storyId" => $row['storyId'], "language" => $row['language'], "approved" => $row['approved']));
        }
    } else {
        array_push($data_arr, array("title" => "none", "currProjId" => "0", "storyId" => "0", "language" => "", "approved" => "0"));
    }
    echo json_encode($data_arr);

}
