<?php

// Define constant to indicate we're running under PHPUnit
define('PHPUNIT_RUNNING', true);

// Disable output buffering for tests
ob_start();

// Autoload PHPMailer classes
require_once __DIR__ . '/../www/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../www/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../www/PHPMailer/src/SMTP.php';

// Load composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Helper to capture output and clean buffer
function captureOutput($callback) {
    ob_start();
    try {
        $callback();
        $output = ob_get_clean();
        return $output;
    } catch (Exception $e) {
        ob_end_clean();
        throw $e;
    }
}

// Helper to reset global state between tests
function resetGlobalState() {
    $_SERVER = [];
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    $_COOKIE = [];
    $_SESSION = [];
    $_ENV = [];
    $_REQUEST = [];
    // Clear test headers
    if (function_exists('clear_test_headers')) {
        clear_test_headers();
    }
}
