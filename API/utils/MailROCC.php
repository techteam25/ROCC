<?php
require '../classes/class.phpmailer.php';
require '../classes/class.smtp.php';
require '../../classes/class.mailsettings.php';

function SendMailROCCUser($from, $to, $subject, $message) {
    $Headers = "MIME-Version: 1.0" . "\r\n";
    $Headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $Headers .= "From: $from";
    error_log("SendMailROCCUser mail sent: $to / $subject / $message / $Headers");
    mail($to, $subject, $message, $Headers, "-f donotreply@ttapps.org");
}
?>
