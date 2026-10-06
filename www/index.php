<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once('auth.php');

function send_email($config) {
  // echo print_r($config, true);
  $mail = new PHPMailer;
  $mail->isSMTP();
  $mail->SMTPDebug = 0; // 0 = off (for production use) - 1 = client messages - 2 = client and server messages
  $mail->Host = $config['smtp_host']; // use $mail->Host = gethostbyname('smtp.gmail.com'); // if your network does not support SMTP over IPv6
  $mail->Port = $config['smtp_port']; // TLS only
  $mail->SMTPSecure = 'tls'; // ssl is depracated
  $mail->SMTPAuth = true;
  $mail->Username = $config['smtp_user'];
  $mail->Password = $config['smtp_pass'];
  $mail->setFrom($config['from_email'], $config['from_name']);
  $mail->addAddress($config['to_email']);
  $mail->Subject = $config['subject'];
  $mail->msgHTML($config['message_html']); //$mail->msgHTML(file_get_contents('contents.html'), __DIR__); //Read an HTML message body from an external file, convert referenced images to embedded,
  if (isset($config['message_plain'])) {
    $mail->AltBody = $config['message_plain'];
  }
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

$config_filename = 'config.ini';
$ini_config = parse_ini_file($config_filename, FALSE);
if ($ini_config === FALSE) {
  http_response_code(500);
  exit;
}

if (!isset($ini_config['api_token'])) {
  http_response_code(500);
  exit;
}

if ($ini_config['api_token'] !== get_bearer_token()) {
  http_response_code(401);
  exit;
}

// Parse JSON request body
$request_body = file_get_contents('php://input');
$request_data = json_decode($request_body, true);

if ($request_data === null && json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
  exit;
}

$mail_config_keys = [
  'to_email',
  'subject',
  'message_html',
  'message_plain',
  'from_email',
  'from_name',
  'smtp_host',
  'smtp_user',
  'smtp_pass',
  'smtp_port',
];

$mail_config = [];
$missing_field_names = [];

foreach ($mail_config_keys as $key) {
  if ($key === 'message_plain' && !isset($request_data[$key])) {
    continue;
  }
  if (!isset($request_data[$key]) && !isset($ini_config[$key])) {
    $missing_field_names[] = $key;
    continue;
  }
  $mail_config[$key] = trim($request_data[$key] ?? $ini_config[$key]);
}

// Handle attachments from JSON (optional)
if (isset($request_data['attachments'])) {
  $mail_config['attachments'] = $request_data['attachments'];
}

if (sizeof($missing_field_names) > 0) {
  $missing_field_names_str = implode(', ', $missing_field_names);
  http_response_code(400);
  echo json_encode(['error' => "Missing fields: {$missing_field_names_str}"]);
  exit;
}

$result = send_email($mail_config);
http_response_code($result ? 200 : 400);
if ($result) {
  echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
}
