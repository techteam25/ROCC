<?php 
$mail = new PHPMailer;

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'smtp.mailgun.org';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'postmaster@mg.techteam.org';                 // SMTP username
$mail->Password = '55b0ca726d2a211fec68027316dd1cfd';                           // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to

$mail->From = 'postmaster@mg.techteam.org';
$mail->FromName = 'TECH Website Email';
$mail->addReplyTo('tim@techteam.org', 'Tim');
$mail->isHTML(true);                                  // Set email format to HTML
?>
