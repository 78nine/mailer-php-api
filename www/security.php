<?php

function check_authentication($config) {
  if (!isset($config['api_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
  }

  if ($config['api_token'] !== get_bearer_token()) {
    http_response_code(401);
    exit;
  }
}

function enforce_rate_limit($config) {
  $rate_limit_enabled = isset($config['rate_limit_enabled']) ? (bool)$config['rate_limit_enabled'] : true;
  
  if (!$rate_limit_enabled) {
    return;
  }

  $max_requests = isset($config['rate_limit_max']) ? (int)$config['rate_limit_max'] : 10;
  $time_window = isset($config['rate_limit_window']) ? (int)$config['rate_limit_window'] : 60;
  
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
