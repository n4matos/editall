<?php

require 'env.php';
require 'Security.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use GuzzleHttp\Client;

//Load Composer's autoloader
require 'vendor/autoload.php';

$mail = new PHPMailer(true);

$security = new Security();
$client = new Client();

if (isset($_POST['name']) AND isset($_POST['email']) AND isset($_POST['message']) AND isset($_POST['g-recaptcha-response']))
{
    $name = $security->xss_clean($_POST['name']);
    $email = $security->xss_clean($_POST['email']);
    $message = $security->xss_clean($_POST['message']);
    
    // Prefedined Variables  
    $set_from = 'Rinjani Notification Mailer';
    $subject = 'Rinjani: Message from ' . $name . '!';

    // Collecting all content in HTML Table
    $content = '<table width="100%">
    <tr><td colspan="2"><strong>Contact Details:</strong></td></tr>
    <tr><td valign="top">Name:</td><td>' . $name . '</td></tr>
    <tr><td valign="top">Email:</td><td>' . $email . '</td></tr>
    <tr><td valign="top">Message:</td><td>' . $message . '</td></tr>
    </table> ';
    
    try {

        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => GOOGLE_SECRET,
                'response' => $_POST['g-recaptcha-response'],
            ]
        ]);
        
        $send = false;
        if(json_decode($response->getBody())->success) {
            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            //Recipients
            $mail->setFrom($email, $set_from);
            $mail->addAddress(SMTP_USERNAME, $subject);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            if ($mail->send()) {
                $send = true;
            }
        }

        echo json_encode($send);
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}