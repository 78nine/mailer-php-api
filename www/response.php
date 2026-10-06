<?php

function send_success_response() {
  http_response_code(200);
  echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
}

function send_error_response() {
  http_response_code(400);
  // Error message already echoed by send_email function
}
