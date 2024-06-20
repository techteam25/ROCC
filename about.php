<?php
    session_start();

    if (!isset($_SESSION['email'])) {
        header("Location: login.php");
    }

    //if ($_SESSION['admin'] === false){
        //header("Location: index.php");
    //}

    require_once('API/utils/Model.php');

?>

<!doctype html>
<html lang = "en-US">

<head>
    <meta charset="utf-8">
    <meta https-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel = "stylesheet" href = "css/styles.css" type = "text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Abel|Raleway|Roboto|Quicksand|Lora|PT+Serif|Sulphur+Point&display=swap" rel="stylesheet">
</head>


<body>
<div class ="container">
    
	<div class="flex-header">
		<div class ="header-top">
			<div class="story-info">
				<img id ="logo" src='images/SP.png' width="60" height="60">
				<h1>ROCC for SPadv</h1>
			</div>

			<div class ="header-menu">
				<a href="index.php">Dashboard</a>
				<a href="logout.php">Logout</a>
			</div>
		</div>
	</div>

    <div class = "flex-content">

    <div class="banner">About</div>
        <tr>ROCC for SPadv 2.0</tr>
        <br><br>
            <tr>
The Remote Oral Consultant Checker for Story Publisher Adv (ROCC4SPadv) software is jointly copyrighted &copy; by Tyndale Bible Translators and Robin Rempel, 2022.
            </tr>
        <br><br>
            <tr>
Acknowledgment and much gratitude is extended to three teams of senior software engineering design students and their professors-advisors from Cedarville University, USA between the years of 2016-2020 for their generous contribution of building and developing ROCC prototype code and user interface along with the necessary corresponding prototype code and user interface utilized in the Story Publisher Adv (SP app), remote version, and setting up the server system that interfaces between ROCC and SP app. Much thanks to God is also extended for the graphic artist and software engineering professionals and interns at Tech Team Advantage for their significant contributions in rebasing, updating and maintaining the ROCC and Story Publisher Adv (SPadv) software so that the Bibleless ethnic groups of the world might be able to help themselves have access to God's Word in their own languages.
            </tr>    
             <br><br>
            <tr>
The Remote Oral Consultant Checker for Story Publisher Adv (ROCC4SPadv) web app is released under the following license: Permission is hereby granted, free of charge, to any person lawfully obtaining a copy of this software and associated documentation files (the "software") to use and redistribute the software, to build and distribute videos from text and audio-visual data, provided that the are the owner of the data or have permission to distribute it. The Software is protected by international copyright treaty provisions. No person may remove any copyright or other proprietary notice from the software nor reverse engineer, decompile, or disassemble the software. THE SOFTWARE IS PROIVED "AS IT", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNES FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABLITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OF THE USE OR OTHER DEALINGS IN THE SOFTWARE.
            </tr>       

    </div>
</div>

</body>
</html> 
