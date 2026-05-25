<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

function sendVerificationEmail(string $to, string $login, string $token): bool
{
    $config = require __DIR__ . '/config_mail.php';

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $verifyLink = $scheme . '://' . $host . '../pages/registration.php?verify_token=' . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->Port = (int)$config['port'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to, $login);

        $mail->isHTML(true);
        $mail->Subject = 'Подтверждение регистрации в магазине «Лавка»';

        $safeLogin = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <p>Здравствуйте, <strong>{$safeLogin}</strong>!</p>
            <p>Для подтверждения регистрации перейдите по ссылке:</p>
            <p><a href=\"{$safeLink}\">{$safeLink}</a></p>
            <p>Если вы не регистрировались на сайте, просто проигнорируйте это письмо.</p>
        ";

        $mail->AltBody =
            "Здравствуйте, {$login}!\n\n" .
            "Для подтверждения регистрации перейдите по ссылке:\n" .
            $verifyLink . "\n\n" .
            "Если вы не регистрировались на сайте, просто проигнорируйте это письмо.";

        return $mail->send();

    } catch (Exception $e) {
        $debugDir = __DIR__ . '/../storage';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        $log = "=== " . date('Y-m-d H:i:s') . " ===\n";
        $log .= "To: {$to}\n";
        $log .= "Login: {$login}\n";
        $log .= "Error: " . $mail->ErrorInfo . "\n";
        $log .= "Verify link: {$verifyLink}\n\n";

        file_put_contents($debugDir . '/mail_debug.log', $log, FILE_APPEND);

        return false;
    }
}
