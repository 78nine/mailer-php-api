<?php

header('Content-Type: application/json; charset=utf-8');

// Restrict to POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  echo json_encode(['error' => 'Method not allowed. Use POST.']);
  exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once('auth.php');
require_once('rate_limit.php');

function validate_email($email) {
  $email = trim($email);
  if (empty($email)) {
    return false;
  }
  // Use PHP's built-in email validation
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function send_email($config) {
  // echo print_r($config, true);
  $mail = new PHPMailer;
  $mail->isSMTP();
  $mail->CharSet = PHPMailer::CHARSET_UTF8;
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
  if (isset($config['bcc'])) {
    $bcc_addresses = is_array($config['bcc']) ? $config['bcc'] : explode(',', $config['bcc']);
    foreach ($bcc_addresses as $bcc_address) {
      $bcc_address = trim($bcc_address);
      if (!empty($bcc_address)) {
        $mail->addBCC($bcc_address);
      }
    }
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

// Rate limiting
$rate_limit_enabled = isset($ini_config['rate_limit_enabled']) ? (bool)$ini_config['rate_limit_enabled'] : true;
if ($rate_limit_enabled) {
  $max_requests = isset($ini_config['rate_limit_max']) ? (int)$ini_config['rate_limit_max'] : 10;
  $time_window = isset($ini_config['rate_limit_window']) ? (int)$ini_config['rate_limit_window'] : 60;
  
  $rate_check = check_rate_limit($max_requests, $time_window);
  
  if (!$rate_check['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . $rate_check['retry_after']);
    echo json_encode([
      'error' => 'Too many requests. Please try again later.',
      'retry_after' => $rate_check['retry_after']
    ]);
    exit;
  }
  
  // Cleanup old rate limit files occasionally (1% chance)
  if (rand(1, 100) === 1) {
    cleanup_rate_limit_files();
  }
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

// Handle optional BCC from JSON or config
if (isset($request_data['bcc'])) {
  $mail_config['bcc'] = $request_data['bcc'];
} elseif (isset($ini_config['bcc'])) {
  $mail_config['bcc'] = $ini_config['bcc'];
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

// Validate email addresses
if (!validate_email($mail_config['to_email'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid recipient email address']);
  exit;
}

if (!validate_email($mail_config['from_email'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid sender email address']);
  exit;
}

if (isset($mail_config['bcc'])) {
  $bcc_addresses = is_array($mail_config['bcc']) ? $mail_config['bcc'] : explode(',', $mail_config['bcc']);
  foreach ($bcc_addresses as $bcc_address) {
    $bcc_address = trim($bcc_address);
    if (!empty($bcc_address) && !validate_email($bcc_address)) {
      http_response_code(400);
      echo json_encode(['error' => "Invalid BCC email address: {$bcc_address}"]);
      exit;
    }
  }
}

$result = send_email($mail_config);
http_response_code($result ? 200 : 400);
if ($result) {
  echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
}
