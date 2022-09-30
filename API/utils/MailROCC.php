<?php
require '../classes/class.phpmailer.php';
require '../classes/class.smtp.php';
require '../classes/class.mailsettings.php';

function SendMailROCCUser($from, $to, $subject, $message) {
    $Headers = "From: $from\n" .
	"Reply-to: $from\n";
    error_log("SendMailROCCUser mail sent: $to / $subject / $message / $Headers");
    mail($to, $subject, $message, $Headers);
}
?>
