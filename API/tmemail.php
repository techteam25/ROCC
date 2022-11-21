<?php
require_once('utils/Model.php');
require_once('utils/MailROCC.php');

            $From = "Story Producer Adv <donotreply@ttaapps.org>";
            $Message = "Dan,  I just wanted to let you know that 3 of 6 required audio files have been uploaded  Tim";
            $Subject = "Audio file upload status";
//            $To = "Dan <ark@arkweb.org>";
//            $To = "Tim <tmeadows63@gmail.com>";
//            $To = "Dan <dharding@techteam.org>";
            $To = "Tim <tmeadows@techteam.org>";
            SendMailRoccUser($From, $To, $Subject, $Message);

?>
