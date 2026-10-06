<?php

function parse_json_request() {
  $request_body = file_get_contents('php://input');
  $request_data = json_decode($request_body, true);

  if ($request_data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
  }

  return $request_data;
}

function build_mail_config($request_data, $ini_config) {
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
    // message_plain is optional
    if ($key === 'message_plain' && !isset($request_data[$key])) {
      continue;
    }
    
    if (!isset($request_data[$key]) && !isset($ini_config[$key])) {
      $missing_field_names[] = $key;
      continue;
    }
    
    $mail_config[$key] = trim($request_data[$key] ?? $ini_config[$key]);
  }

  if (sizeof($missing_field_names) > 0) {
    $missing_field_names_str = implode(', ', $missing_field_names);
    http_response_code(400);
    echo json_encode(['error' => "Missing fields: {$missing_field_names_str}"]);
    exit;
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

  return $mail_config;
}
