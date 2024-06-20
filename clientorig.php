<?php
// Dashboard.html: This will be the page that displays all the projects available to the logged-in consultant.

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
}

$isAdmin = $_SESSION['admin'];
require_once('API/utils/Model.php');

$conn = GetDatabaseConnection();

//display currStory and currProjId, passed from index.php
if (array_key_exists('story', $_GET)) {

    $storyId = $_GET['story'];
    $storyStmt = PrepareAndExecute($conn, 
        'SELECT androidId, title FROM Stories, Projects WHERE Projects.id = projectId AND Stories.id = ?', 
        array($storyId));
    if (($row = $storyStmt->fetch(PDO::FETCH_ASSOC))) {
        $projectId = $row['androidId'];
        $templateTitle = $row['title'];
    } else {
        RespondWithError(404, 'Story Not Found');
    }
} else {
    RespondWithError(400, 'No Story Requested');
}
?>


<!doctype html>
<html lang = "en-US">

    <head>
        <meta charset="utf-8">
        <meta https-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="favicon.png">
        <title>ROCC: Client</title>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" 
              href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" 
              integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" 
              crossorigin="anonymous">
        <!-- Optional theme -->
        <link rel="stylesheet" 
              href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" 
              integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" 
              crossorigin="anonymous">
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" 
                integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
                crossorigin="anonymous"></script>

        <link href="https://fonts.googleapis.com/css?family=Montserrat|Abel|Raleway|Roboto|Quicksand|Lora|PT+Serif|Sulphur+Point&display=swap" rel="stylesheet">
        <script src="//cdn.quilljs.com/1.3.6/quill.js"></script>
        <script src="//cdn.quilljs.com/1.3.6/quill.min.js"></script>

        <!-- Theme included stylesheets -->
        <link href="//cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <link href="//cdn.quilljs.com/1.3.6/quill.bubble.css" rel="stylesheet">

        <!-- Core build with no theme, formatting, non-essential modules -->
        <link href="//cdn.quilljs.com/1.3.6/quill.core.css" rel="stylesheet">

        <link rel = "stylesheet" href = "css/client.css" type = "text/css"/>

        <script>

        <?php
        $templateRoot = "Files/Templates/$templateTitle";
        $storyRoot = "Files/Projects/$projectId/$storyId";
        $files = scandir($templateRoot);
        $slideFiles = [];
        natsort($files);
        $slideCount = 0;

        //sort files in numeric order instead of alphabetical order
        $image_extensions = [];
        foreach ($files as $file) {
            if (strpos(basename($file), ".jpg") || strpos(basename($file), ".png")) {
                $slideFiles[$slideCount] = $file;
                $filename = pathinfo($file);
                array_push($image_extensions, $filename['extension']);
                $slideCount++;
            }
        }
        ?>


        // Capture vars from PHP
        var image_extensions = <?=json_encode($image_extensions)?>;
        var slideCount = <?=json_encode($slideCount)?>;
        var storyId = <?=json_encode($storyId)?>;
        var projectId = <?=json_encode($projectId)?>;
        var templateRoot = <?=json_encode($templateRoot)?>;
        var websocketPort = <?=json_encode($GLOBALS['websocketPort'])?>;

        </script>

    </head>

    <body>
        <div class="content-body">
            <div class="header">
                <a href="index.php">
                    <img id="logo" src="images/SP.png" width="60" height="60">
                </a>

                <!--Get current project number and story title-->
                <div class="story-info">
                    <h1 id="storyTitle">Story Publisher Adv</h1>
                </div>

                <div class="header-menu">
                    <a id="admin-link" href="admin.php">Admin</a>
                    <a href="index.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>

            <div class="grid-container">

                <!--Thumbnail section on left-hand side-->
                <div class="slides">
                    <!--Pull in all .jpg files for the thumbnails -->
                    <?php foreach ($slideFiles as $file):
                        //check if file is .JPG
                        $filename = pathinfo($file);
			$name = $filename['filename']; 
			$numberOfSlide = $name;
			$amountOfSlides = count($slideFiles) - 1; // Subtracted 1 to start counting from zero
			//Searching for the song audio!!!
			$songAudioFile = '/var/www/html/' . $storyRoot . '/' . $amountOfSlides . '.m4a';
			if(file_exists($songAudioFile)){
			//"Creating" the song slide!!!
				if($name == $amountOfSlides){
		?>
					<script>let songSlideNumber = <?=$name?></script>
			<?php
					$name = 'Song';
					$file = '../../../images/songBackGround.jpg';
				}
			}else{
				?><script>let songSlideNumber = (-1)</script><?php
			}

			?>
			

                        <div class="tn_text" id="thumbnail_text_<?=$name?>">
                            <div id="tn_slide"><?=$name?></div>
                            <div id="msgImg<?=$name?>" style="display:none;">
                                <img src="images/msg.png" width="30px" height="30px"> 
                            </div>
                            <div id="checkImg<?=$name?>" style="display:none;">
                                <img src="images/cmark.png" width="20px" height="20px">
                            </div>
                        </div>
                        <div class="thumbnail" id="thumbnail <?=$name?>" onclick="changeSlide(parseInt(<?=$numberOfSlide?>), currentSlide)">
                            <img src="<?=$templateRoot?>/<?=$file?>">
                        </div>
                    <?php endforeach; ?>
                </div>


                <div class="audio">

                    <!--display current slide-->
                    <div class="left-audio">
                        <div id ="lf-t"> Current Slide </div>

			<div class="currSlide">
				<div style="width=10%">

                            <button id="prevSlide" onclick="changeSlide(currentSlide - 1, currentSlide)">
                                <img id ="p-bt" src="images/back.jpg">
                            </button>
</div>
                            <div class="currSlideInner" style="width=90%">

                                <div class="approvedTitle">
                                    <p id="status"></p>
                                    <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; approved status:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
                                    <label class="switch">
                                        <input id="approveSwitch" 
                                               onclick="approveSwitchChanged(currentSlide, this, event)" 
                                               type="checkbox">
                                        <span class="slider round"></span>
                                    </label>
                                </div>

                                <div class ="transcripts">
                                    <!--These divs is filled by changeSlide() in client.JS-->
                                    <div id="refText"></div>
                                    <div id="mainText"></div>
                                </div>

                                <!--Get audio file and put it inthe player-->
                                <div class ="theAudioPlayer">
                                <audio controls id="audioPlayer">
                                    <source id="mainAudio" 
                                            src="<?=$storyRoot?>/0.m4a" 
                                            type="audio/x-m4a"></source>
                                </audio>
                                </div>
                            </div>

				<div style="width=10%">

                            <button id="fwdSlide" onclick="changeSlide(currentSlide + 1, currentSlide)">
                                <img id="f-bt" src="images/forward.jpg">
                            </button>
                            </div>

                        </div>
                    </div>

                    <div class ="right-audio">
                        <div id ="rt-t"> Current Slide Notes </div>
                        <div class ="written-notes">
                            <form>
                                <div id ="editor-container"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="messages">
                    <div id="m-t"> Messages </div>
                    <div id="messagesContainer">
                    </div>
                    <div class="msg">
                        <input class="msg-input" 
                               id="sendMessageInput" 
                               onkeypress="sendMessageInputKeyPress(event)" 
                               type="text" 
                               placeholder="Enter a message ...">
                        <button id="msg-send" onclick="sendMessage()" type="button" value="Send">
                            <img id ="send-img" src="images/send.png" width="25px" height="25px"></img>
                        </button>
                    </div>
                </div>

                <div class="lookup">
                    <div class ="w-story">
                        <div class ="w-trans">
                            <form id = "ws-form">
                                <div id ="editor-container2"></div>
                            </form>
                        </div>
                        <div class ="w-audio">
                            <audio controls id="wholeAudio">
                                <source id="wholeAudio" 
                                        src="<?=$storyRoot?>/wholeStory.m4a" 
                                        type="audio/x-m4a">
                            </audio>
			<!-- Removing the save button becuase it is not necessary anymore:::
                            <button id="saveButton2" onclick="saveWholeNotes()" 
                                    type="button" 
				    value="Save">Save Whole Story Notes</button>
-->
                        </div>
                    </div>
                </div>

                <div class ="bible">
                    <div class="tabHeader">
                        <button class="tablinks" onclick="openTab(event, 'bible-t')"> Bible Text Lookup </button>
                        <button class="tablinks" onclick="openTab(event, 'WordLinks')"> WordLinks </button>
                    </div>

                    <div id="bible-t" class="tabcontent"><p>Bible text lookup plugin here</p></div>
                    <div id="WordLinks" class="tabcontent"><p>WordLinks here eventually</p></div>

                </div>
            </div>
        </div>

        <script src="client.js"></script>
        <script>
            //check if admin
            var isAdmin = "<?php echo $isAdmin ?>";
            console.log(isAdmin);
            if(!isAdmin){
                var adminLink = document.getElementById("admin-link");
                adminLink.style.display = "none";
            }

        </script>
    </body>
</html>
