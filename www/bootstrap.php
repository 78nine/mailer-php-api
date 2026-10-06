<?php

// Set response headers
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: default-src \'none\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Load configuration
function load_config($filename = 'config.ini') {
  $config = parse_ini_file($filename, FALSE);
  if ($config === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
  }
  return $config;
}

// Setup CORS if enabled
function setup_cors($config) {
  if (isset($config['cors_enabled']) && $config['cors_enabled']) {
    $allowed_origin = isset($config['cors_origin']) ? $config['cors_origin'] : '*';
    header('Access-Control-Allow-Origin: ' . $allowed_origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(204);
      exit;
    }
  } else {
    // If CORS is disabled, add additional security
    header('X-Permitted-Cross-Domain-Policies: none');
  }
}

// Restrict to POST requests only
function enforce_post_method() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
  }
}
