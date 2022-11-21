<?php
require 'classes/class.phpmailer.php';
require 'classes/class.smtp.php';
require 'classes/class.mailsettings.php';

$Headers = "";
$Headers = "MIME-Version: 1.0" . "\r\n";
$Headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$Headers .= "From: Story Producer Adv <donotreply@ttapps.org>" . "\r\n"; 
$Headers .= "Return-Path: Story Producer Adv <donotreply@ttapps.org>" . "\r\n"; 
$Headers .= "Cc: Tim <tmeadows63@gmail.com>"; 

$ToEmail = "Dan <dharding@techteam.org>";
$Msg = "This is a message";
mail($ToEmail, "Email subject line", $Msg, $Headers);
?>
