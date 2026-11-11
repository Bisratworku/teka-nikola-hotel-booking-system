<?php
// lib_mail.php — Mailtrap SMTP via PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function send_mail(string $to, string $subject, string $html): bool
{
    $mail = new PHPMailer(true);

    try {
        // Mailtrap SMTP
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_USER') ? MAIL_USER : '';
        $mail->Password   = defined('MAIL_PASS') ? MAIL_PASS : '';
        $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 2525;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        // Sender
        $from_addr = defined('MAIL_FROM_ADDR') ? MAIL_FROM_ADDR : 'no-reply@yourhotel.test';
        $from_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'The Riverside Reservations';
        $mail->setFrom($from_addr, $from_name);

        // Recipient (Mailtrap will catch everything in the inbox)
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags(
            preg_replace('/<br\s*\/?>/i', "\n", preg_replace('/<\/p>/i', "\n\n", $html))
        );

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}