<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($config) {
  $mail = new PHPMailer;
  $mail->isSMTP();
  $mail->CharSet = PHPMailer::CHARSET_UTF8;
  $mail->SMTPDebug = 0; // 0 = off (for production use) - 1 = client messages - 2 = client and server messages
  $mail->Host = $config['smtp_host'];
  $mail->Port = $config['smtp_port'];
  $mail->SMTPSecure = 'tls';
  $mail->SMTPAuth = true;
  $mail->Username = $config['smtp_user'];
  $mail->Password = $config['smtp_pass'];
  
  // Set sender and recipient
  $mail->setFrom($config['from_email'], $config['from_name']);
  $mail->addAddress($config['to_email']);
  
  // Set subject and body
  $mail->Subject = $config['subject'];
  $mail->msgHTML($config['message_html']);
  
  if (isset($config['message_plain'])) {
    $mail->AltBody = $config['message_plain'];
  }
  
  // Add BCC recipients
  if (isset($config['bcc'])) {
    $bcc_addresses = is_array($config['bcc']) ? $config['bcc'] : explode(',', $config['bcc']);
    foreach ($bcc_addresses as $bcc_address) {
      $bcc_address = trim($bcc_address);
      if (!empty($bcc_address)) {
        $mail->addBCC($bcc_address);
      }
    }
  }
  
  // Add attachments
  if (isset($config['attachments']) && is_array($config['attachments'])) {
    foreach ($config['attachments'] as $attachment) {
      if (isset($attachment['content']) && isset($attachment['filename'])) {
        $decoded = base64_decode($attachment['content']);
        $mail->addStringAttachment($decoded, $attachment['filename'], 'base64', $attachment['type'] ?? '');
      }
    }
  }

  $success = $mail->send();
  
  if (!$success) {
    echo "Mailer Error: {$mail->ErrorInfo}";
  }
  
  return $success;
}
