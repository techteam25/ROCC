<?php
// Dashboard.html: This will be the page that displays all the projects available to the logged-in consultant.

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
}

if (isset($_SESSION['admin'])) {
	$isAdmin = $_SESSION['admin'];
} else {
	$isAdmin = false;
}
require_once('API/utils/Model.php');

$conn = GetDatabaseConnection();

$storyId = '';
//display currStory and currProjId, passed from index.php
if (array_key_exists('story', $_GET)) {

    $storyId = $_GET['story'];
    $storyStmt = PrepareAndExecute($conn, 
        'SELECT androidId, title, Stories.language AS language FROM Stories, Projects WHERE Projects.id = projectId AND Stories.id = ?', 
        array($storyId));
    if (($row = $storyStmt->fetch(PDO::FETCH_ASSOC))) {
        $projectId = $row['androidId'];
        $language = $row['language'];
        $templateTitle = $row['title'];
    } else {
        RespondWithError(404, 'Story Not Found');
    }
} else {
    RespondWithError(400, 'No Story Requested');
}

$wordLinkFilePath = __DIR__ . '/data/' . ($language ?: "en") . '/wordlinks.csv';
$handle = fopen($wordLinkFilePath, 'r');

# get list of terms having WordlinkTranslations
$sql = "select lower(WT.term) from WordlinkTranslations WT join Stories S on WT.projectId = S.projectId where S.id = ?;";
$stmt = PrepareAndExecute($conn, $sql, array($storyId));
$termsWithBackTranslations = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch only the first column
$wordLinkTerms = [];

// parse header row and ignore data
fgetcsv($handle);
// Read the file line by line
while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
// skip empty row if there is any
    if (isset($row[1]) && !empty($row[1])) {
        $alternateForms = "";
        $backTranslations = "";
        $relatedTerms = "";

        if (!empty($row[2])) {
            $alternateForms = explode(",", $row[2]);
        }

        if (!empty($row[3])) {
            $backTranslations = explode(";", $row[3]);
        }

        if (!empty($row[5])) {
            $relatedTerms = explode(",", $row[5]);
        }

        $wordLinkTerms[strtolower(trim($row[1]))] = [
            "alternateTerms" => $alternateForms,
            "otherLanguageExamples" => $backTranslations,
            "notes" => trim($row[4] ?? ""),
            "relatedTerms" => $relatedTerms,
            "otherComments" => trim($row[6] ?? ""),
            "galensRationale" => trim($row[7] ?? ""),
            "galensNotes" => trim($row[8] ?? ""),
            "otherConsultantComments" => trim($row[9] ?? ""),
            "otherConsultantSuggestions" => trim($row[10] ?? ""),
            'hasWordLinkTranslations' => in_array(strtolower(trim($row[1])), $termsWithBackTranslations),
        ];
    }
}

// Close the file handle
fclose($handle);
ksort($wordLinkTerms);
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
        $templateRoot = "Files/Templates/";
        if ($language !== "") {
            $templateRoot = $templateRoot . $language . "/";
        }
        $templateRoot = $templateRoot . $templateTitle;
        $storyjsonfile = $templateRoot . "/project/story.json";
        if (file_exists($storyjsonfile))
        {
            $storyjsonstring = file_get_contents($storyjsonfile);
            $storyjsonobj = json_decode($storyjsonstring);
        }
        $storyRoot = "Files/Projects/$projectId/$storyId";
        $slideFiles = [];
        $slideCount = 0;

        //sort files in numeric order instead of alphabetical order
        $image_extensions = [];
        $length = count($storyjsonobj->slides);
        for ($i = 0; $i <= $length - 1; $i++)
        {
            $slide = $storyjsonobj->slides[$i];
            if (strcmp($slide->slideType, "COPYRIGHT"))  // totally skip copyright slide
            {
                if (!strcmp($slide->slideType, "FRONTCOVER")) {
                    $file = '../../../images/TitleSlide.jpg';
                    if ($language !== "") {
                        $file = '../' . $file;
                    }
                }
                else if (!strcmp($slide->slideType, "NUMBEREDPAGE")) {
                    $file = $slide->imageFile;
                }
                else if (!strcmp($slide->slideType, "LOCALSONG")) {
                    $file = '../../../images/SongSlide.jpg';
                    if ($language !== "") {
                         $file = '../' . $file;
                    }
                }
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
        var externalWebsocketPort = <?=json_encode($GLOBALS['externalWebsocketPort'])?>;
        var externalWebsocketHost = <?=json_encode($GLOBALS['externalWebsocketHost'])?>;

        const wordLinkTerms = <?= json_encode($wordLinkTerms) ?>;
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
                    <h1 id="storyTitle">ROCC for SPadv</h1>
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
<?php
		    $amountOfSlides = count($slideFiles) - 1; // Subtracted 1 to start counting from zero
	            $numberOfSlide = -1;
		    foreach ($slideFiles as $file):
                        //check if file is .JPG
                        $filename = pathinfo($file);
			//$name = $filename['filename']; 
			$numberOfSlide++;
			//Searching for the song audio!!!
			$songAudioFile = $filename['dirname'] . "/" . $storyRoot . '/' . $amountOfSlides . '.m4a';
			if(file_exists($songAudioFile)){
			//"Creating" the song slide!!!
				if($numberOfSlide == $amountOfSlides){
		?>
					<script>songSlideNumber = <?=$amountOfSlides?></script>
<?php
					//$name = 'Song';
					$file = '../../../images/SongSlide.jpg';
				}
			}else{
				?><script>songSlideNumber = (-1)</script><?php
			}

			?>
			

                        <div class="tn_text" id="thumbnail_text_<?=$numberOfSlide?>">
                            <div id="tn_slide"><?=$numberOfSlide?></div>
                            <div id="msgImg<?=$numberOfSlide?>" style="display:none;">
                                <img src="images/msg.png" width="30px" height="30px"> 
                            </div>
                            <div id="checkImg<?=$numberOfSlide?>" style="display:none;">
                                <img src="images/cmark.png" width="20px" height="20px">
                            </div>
                        </div>
			<div class="thumbnail" id="thumbnail <?=$numberOfSlide?>" onclick="changeSlide(parseInt(<?=$numberOfSlide?>), currentSlide)">
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
                                    <p>&nbsp;&nbsp;&nbsp; approved status:&nbsp;&nbsp;&nbsp;</p>
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
                                <!--Get audio file and put it inthe player-->
                                <div class ="theAudioPlayer">
                                <audio controls id="audioPlayer">
                                    <source id="mainAudio" 
                                            src="<?=$storyRoot?>/0.m4a" 
                                            type="audio/x-m4a"></source>
                                </audio>
                                </div>  
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
                        <div class ="w-trans">
                            <form id = "ws-form">
                                <div id ="editor-container2"></div>
                            </form>
                        </div>

                    </div>
                </div>
                <div class ="bible">
                    <div class="tabHeader">
                        <button class="tablinks active" onclick="openTab(event, 'WordLinks')"> WordLinks </button>
                        <button class="tablinks" onclick="openTab(event, 'bible-t')"> Bible Text Lookup </button>
                    </div>
                    <div id="WordLinks" class="tabcontent show active wordlinks">
                        <div id="termList" class="term-list">
                            <input class="form-control" id="termListSearchBox" type="text" placeholder="Search" aria-label="Search">
                            <div class="list-group">
                                <button id="noTermFound" type="button" class="hide list-group-item">Search term not matched</button>
                                <?php foreach ($wordLinkTerms as $key => $value): ?>
                                <button onclick="showTermDetails(event, '<?= rawurlencode($key) ?>')" type="button" class="list-group-item"><?= $key ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div id="termDetails" class="hide"></div>
                    </div>
                    <div id="bible-t" class="tabcontent "><p>Bible text lookup plugin here</p></div>
                </div>
        </div>
        <script src="wordlink-search-tree.js"></script>
        <script>
<?php
    if (file_exists($storyjsonfile))
    {
            // newlines cause parser to fail
        $string = str_replace('\n', "<BR>", $storyjsonstring);
?>
        //when transferring from php to json just output the string, otherwise escaped charcacters are not handled properly
        let json_a = <?php echo $storyjsonstring ?>;
        function readProperties(slideNumber) {
            document.getElementById("storyTitle").innerHTML = json_a.title;
            let currSlide = json_a.slides[slideNumber]
            if (currSlide.reference == "") {
                document.getElementById("lf-t").innerHTML = "&nbsp;";
            } else {
                document.getElementById("lf-t").innerHTML = currSlide.reference;
            }

            const wordLinkPhrases = window.WSL.splitOnWordLinks(currSlide.content);
            const slideContentContainer = document.createElement('p');
            wordLinkPhrases.forEach(word => {
                if (wordLinkTerms.hasOwnProperty(word.toLowerCase())) {
                    const link = document.createElement('a');
                    link.textContent = word;

                    // display word link term in the slide using black color if there is a backtranslation exits
                    if (wordLinkTerms[word.toLowerCase()].hasWordLinkTranslations) {
                        link.classList.add('hasWordLinkTranslations')
                    }

                    link.addEventListener('click', (event) => {
                        showTermDetails(event, word);
                    });

                    // append word link to display term details
                    slideContentContainer.append(link)
                } else {
                    slideContentContainer.append(word);
                }
            });

            document.getElementById("mainText").replaceChildren(slideContentContainer);
        }

<?php
    }
    else
    {
?>
        function readTextFile(file)
        {
            let rawFile = new XMLHttpRequest();
            rawFile.open("GET", file, false);
            rawFile.onreadystatechange = function ()
            {
                if (rawFile.readyState === 4)
                {
                    if (rawFile.status === 200 || rawFile.status == 0) {
                        let allText = rawFile.responseText;

                        let title = allText.split("~")[0] + ": " + allText.split("~")[1];
                        let slideref = allText.split("~", 4)[2];
                        let trans = allText.split("~", 4)[3];

            setPropertiesCommon(title, slideref, trans);

                    }else{
                        fileDisplayArea = document.getElementById("mainText");
                        fileDisplayArea.innerText = " ";
                    }
                }
            }
            rawFile.send(null);
        }
<?php
    }
?>
        function setPropertiesCommon(title, reference, content)
        {
            document.getElementById("storyTitle").innerHTML = title;
            document.getElementById("lf-t").innerHTML = reference;
            fileDisplayArea = document.getElementById("mainText");
            fileDisplayArea.innerHTML = content;
        }
        function setProperties(slideNumber)
        {
            console.log("in setProperties");
<?php           if (file_exists($storyjsonfile)) { ?>
            readProperties(slideNumber);
<?php           } else {?>
            readTextFile(`${templateRoot}/${slideNumber}.txt`);
<?php       } ?>
        }
        </script>

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
        <template id="termDetailTemplate">
            <div class="termHeader">
                <a href="javascript:void(0)" onclick="showTermList()" class="closeBtn">
                    <span class="glyphicon glyphicon-arrow-left"></span>
                </a>
                <h2 style="margin-top:auto;"></h2>
            </div>
            <div class="termExplanation">
                <ul class="alternateTerms"></ul>
                <div><b>Back translation:</b><ul class="backTranslationList"></ul></div>
                <div><b>Meaning notes. Definitions:</b><p class="notes"></p></div>
                <div class="relatedTerms"><b>Related (but different) terms:</b>
                    <ul class="relatedTermsList"></ul>
                </div>
                <div class="otherLanguageExamples"><b>Other language examples:</b>
                    <ul class="otherLanguageExamplesList"></ul>
                </div>
            </div>
        </template>
    </body>
</html>