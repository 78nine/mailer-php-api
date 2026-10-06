<?php

// Load dependencies
require_once('bootstrap.php');
require_once('auth.php');
require_once('rate_limit.php');
require_once('security.php');
require_once('request.php');
require_once('validation.php');
require_once('response.php');

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once('email.php');

// 1. Initialize
$config = load_config();
setup_cors($config);
enforce_post_method();

// 2. Authenticate
check_authentication($config);

// 3. Rate limiting
enforce_rate_limit($config);

// 4. Parse request
$request_data = parse_json_request();
$mail_config = build_mail_config($request_data, $config);

// 5. Validate
validate_attachment_sizes($mail_config, $config);
validate_email_addresses($mail_config);

// 6. Send email
$result = send_email($mail_config);

// 7. Respond
if ($result) {
  send_success_response();
} else {
  send_error_response();
}
