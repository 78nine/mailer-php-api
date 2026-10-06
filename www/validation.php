<?php

function validate_email($email) {
  $email = trim($email);
  if (empty($email)) {
    return false;
  }
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_email_addresses($mail_config) {
  // Validate recipient email
  if (!validate_email($mail_config['to_email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid recipient email address']);
    exit;
  }

  // Validate sender email
  if (!validate_email($mail_config['from_email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sender email address']);
    exit;
  }

  // Validate BCC addresses
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
}

function validate_attachment_sizes($mail_config, $config) {
  if (!isset($mail_config['attachments'])) {
    return;
  }

  $max_attachment_size = isset($config['max_attachment_size']) ? (int)$config['max_attachment_size'] : 10485760; // 10MB
  $max_total_size = isset($config['max_total_attachments_size']) ? (int)$config['max_total_attachments_size'] : 20971520; // 20MB
  
  $total_size = 0;
  
  foreach ($mail_config['attachments'] as $index => $attachment) {
    if (isset($attachment['content'])) {
      // Estimate decoded size (base64 is ~33% larger than original)
      $decoded_size = (strlen($attachment['content']) * 3) / 4;
      
      if ($decoded_size > $max_attachment_size) {
        http_response_code(413);
        echo json_encode([
          'error' => "Attachment too large. Maximum size: " . ($max_attachment_size / 1048576) . "MB",
          'attachment_index' => $index
        ]);
        exit;
      }
      
      $total_size += $decoded_size;
    }
  }
  
  if ($total_size > $max_total_size) {
    http_response_code(413);
    echo json_encode([
      'error' => "Total attachments size too large. Maximum: " . ($max_total_size / 1048576) . "MB"
    ]);
    exit;
  }
}
