<?php
session_start();

//make sure the user is logged in
if (!isset($_SESSION['email'])) {
header("Location: login.php");
}

//get consultant email to pull assigned projects
else {
$consultantEmail = $_SESSION['email'];
}

$isAdmin = $_SESSION['admin'];

require_once('API/utils/Model.php');
?>

	
<!doctype html>
<html lang = "en-US">

	<head>
		<meta charset="utf-8">
		<meta https-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/png" href="favicon.png">
		<title>ROCC: Dashboard</title>
		<link rel = "stylesheet" href = "css/dashboard.css" type = "text/css"/>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

		<script type="text/javascript" src="radialprogress.js"></script>
		<link href="https://fonts.googleapis.com/css?family=Montserrat|Abel|Raleway|Roboto|Quicksand|Lora|PT+Serif|Sulphur+Point&display=swap" rel="stylesheet">
	</head>
	<script src="index.js">
	</script>

	<body>
		<div class = "container">
			<div class="flex-header">
				<div class ="header-top">
					<div class="story-info">
						<img id ="logo" src='images/SP.png' width="60" height="60">
						<h1>StoryProducer</h1>
					</div>

					<div class ="header-menu">
						<a id="about-link" href="about.php">About</a>
						<a id="admin-link" href="admin.php">Admin</a>
						<a href="logout.php">Logout</a>
					</div>
				</div>

			</div>
			<div class = "flex-container">
				<div class = "languages" id = "lang"> 	
					<p id = "pLN">Languages</p>
					<hr id ="lnHR"></hr>
				</div> 
				<div id = "info"> 	
					<div id = "storyData">
						<p id = "pSD"></p>
						<div id = "currStory">
							<div id = "csLeft">
                                <a id = "img_link">
								    <img id = "img_thumb"  width = '60' height = '60' >
                                </a>
								<a id = "view_story">View Story</a>
							</div>
							<div id = "csRight">
                                <a id = "csRightLink"></a>
                            </div>
						</div>
						<div id = "storyProg">
							<div id="progress">
								<div id = "bar-info">
									<p id = "appr"> Approval Progress</p>
									<div id = "bar">
									</div>
								</div>
								<div id = "p-info">
								</div>
							</div>
							<div id = "other-info"></div>
						</div>


					</div>
					<div id = "storyCol">
						<p id = "pSL">Stories</p>
						<hr></hr>
						<div id ="storyList"></div>
					</div>
				</div> 
			</div> 
			<script>

            //check if admin
            var isAdmin = "<?php echo $isAdmin ?>";
                if (!isAdmin){
                    var adminLink = document.getElementById("admin-link");
                    //adminLink.style.display = "none";
		    adminLink.innerHTML = 'Change Password';
                }


			<?php
				     $conn = GetDatabaseConnection();
				     //get consultantId from email
				     $sql = "SELECT id FROM Consultants WHERE email = ?";
				     $stmt = PrepareAndExecute($conn, $sql, array($consultantEmail));
				     $row = $stmt->fetch(PDO::FETCH_ASSOC);
				     $consultantId = $row['id'];

				     $stmt = PrepareAndExecute($conn, 
					     'SELECT DISTINCT Projects.language, Projects.id AS projectId, ConsultantID  
					     FROM Projects, Assigned WHERE Assigned.consultantId = ? AND Assigned.ProjectID = Projects.id', array($consultantId));

				     $lastProj = -1;
				     $projects = [];

				     //get all projects assocaited with curr_consultant
				     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

					     //new project
					     if ($lastProj !== $row['projectId']) {
						     array_push($projects, $row['language']);
					     }
				     }
				     $storyId = $row['storyId'];
				     $currProjId = $row['androidId'];
			?>

				     var storyId = <?=json_encode($storyId)?>;
				     var currProjId = <?=json_encode($currProjId)?>;
				     var projects = <?=json_encode($projects)?>; 
				     var consultantId = <?=json_encode($consultantId)?>;

				     initPage();

				     //changes based on language clicks
				     $(".langDiv").click(function() {
					     var clickedId = jQuery(this).attr("id");
					     var langTitle = document.getElementById("pSD"); 
					     langTitle.innerHTML = '';
					     langTitle.innerHTML = clickedId;

					     $.ajax({
					     data: "language=" + clickedId,
						     url: "API/getLangInfo.php",
						     type: "POST",
						     success: function (data){
							     reloadPage(data);
						     } 
					     });
				     });

				     //changes based on story clicks

			</script>
		</div> 

	</body>
</html>
</script>
</div> 
	</body>
</html>
