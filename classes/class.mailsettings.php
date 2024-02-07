<?php
$mail = new PHPMailer;

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->SMTPAuth = true;                               // Enable SMTP authentication

$mail->Host = 'smtp.something something.com';  // Specify main and backup SMTP servers
$mail->Username = 'donotreply@something something.org';                 // SMTP username
$mail->Password = 'password';

$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to

$mail->FromName = 'TECH Team';
$mail->From = 'donotreply@something something.org';                 // SMTP username

$mail->setFrom('donotreply@something something.org', 'TECH Team');
$mail->isHTML(true);                                  // Set email format to HTML

$headers = "Content-type: text/plain; charset=UTF-8" . "\n";
$headers .= "MIME-Version: 1.0" . "\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\n";

?>
