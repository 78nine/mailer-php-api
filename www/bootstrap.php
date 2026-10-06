<?php

// Global array to track headers (used for testing in CLI mode)
if (!isset($GLOBALS['test_headers'])) {
    $GLOBALS['test_headers'] = [];
}

// Wrapper function for setting headers (testable in CLI mode)
function set_header($header) {
    // In CLI mode or when testing, store in global array
    if (php_sapi_name() === 'cli' || defined('PHPUNIT_RUNNING')) {
        $GLOBALS['test_headers'][] = $header;
    }
    // Always call header() as well (works in web context)
    @header($header);
}

// Function to get headers (works in both CLI and web contexts)
function get_headers_list() {
    if (php_sapi_name() === 'cli' || defined('PHPUNIT_RUNNING')) {
        return $GLOBALS['test_headers'];
    }
    return headers_list();
}

// Function to clear test headers
function clear_test_headers() {
    $GLOBALS['test_headers'] = [];
}

// Set response headers
set_header('Content-Type: application/json; charset=utf-8');

// Security headers
set_header('X-Content-Type-Options: nosniff');
set_header('X-Frame-Options: DENY');
set_header('X-XSS-Protection: 1; mode=block');
set_header('Referrer-Policy: no-referrer');
set_header('Content-Security-Policy: default-src \'none\'');
set_header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

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
    $cors_origin_config = isset($config['cors_origin']) ? $config['cors_origin'] : '*';
    
    // Handle wildcard
    if ($cors_origin_config === '*') {
      set_header('Access-Control-Allow-Origin: *');
    } else {
      // Parse comma-separated list of origins
      $allowed_origins = array_map('trim', explode(',', $cors_origin_config));
      
      // Get the requesting origin
      $request_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
      
      // Check if the request origin is in the allowed list
      if (in_array($request_origin, $allowed_origins, true)) {
        set_header('Access-Control-Allow-Origin: ' . $request_origin);
        set_header('Vary: Origin');
      } elseif (count($allowed_origins) === 1) {
        // Single origin specified - set it directly (legacy behavior)
        set_header('Access-Control-Allow-Origin: ' . $allowed_origins[0]);
      }
      // If origin not matched and multiple origins configured, don't set the header
    }
    
    set_header('Access-Control-Allow-Methods: POST, OPTIONS');
    set_header('Access-Control-Allow-Headers: Content-Type, Authorization');
    set_header('Access-Control-Max-Age: 86400');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(204);
      exit;
    }
  } else {
    // If CORS is disabled, add additional security
    set_header('X-Permitted-Cross-Domain-Policies: none');
  }
}

// Restrict to POST requests only
function enforce_post_method() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
  }
}
