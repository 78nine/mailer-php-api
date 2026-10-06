<?php

function get_client_ip() {
  $ip = $_SERVER['REMOTE_ADDR'];
  
  // Check for proxy headers (only if you trust your proxy)
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ips[0]);
  } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  
  return $ip;
}

function check_rate_limit($max_requests = 10, $time_window = 60) {
  $ip = get_client_ip();
  $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
  
  if (!is_dir($storage_dir)) {
    mkdir($storage_dir, 0700, true);
  }
  
  $ip_hash = hash('sha256', $ip);
  $file = $storage_dir . '/' . $ip_hash . '.json';
  
  $now = time();
  $requests = [];
  
  // Load existing requests
  if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
    if ($data && isset($data['requests'])) {
      $requests = $data['requests'];
    }
  }
  
  // Remove old requests outside time window
  $requests = array_filter($requests, function($timestamp) use ($now, $time_window) {
    return ($now - $timestamp) < $time_window;
  });
  
  // Check if limit exceeded
  if (count($requests) >= $max_requests) {
    $oldest_request = min($requests);
    $retry_after = $time_window - ($now - $oldest_request);
    return [
      'allowed' => false,
      'retry_after' => max(1, $retry_after)
    ];
  }
  
  // Add current request
  $requests[] = $now;
  
  // Save updated requests
  file_put_contents($file, json_encode(['requests' => $requests]), LOCK_EX);
  
  return [
    'allowed' => true,
    'remaining' => $max_requests - count($requests)
  ];
}

function cleanup_rate_limit_files($max_age = 3600) {
  $storage_dir = sys_get_temp_dir() . '/mailer_rate_limit';
  
  if (!is_dir($storage_dir)) {
    return;
  }
  
  $now = time();
  $files = glob($storage_dir . '/*.json');
  
  foreach ($files as $file) {
    if (($now - filemtime($file)) > $max_age) {
      @unlink($file);
    }
  }
}
