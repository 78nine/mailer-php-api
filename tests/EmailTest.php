<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;

class EmailTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSendEmailConfiguresSmtpSettings()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML message</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass'
        ];
        
        // We can't easily test the actual sending without a real SMTP server
        // But we can verify the function doesn't throw errors with valid config
        // and returns a boolean
        
        // Note: This will fail to send but shouldn't throw an exception
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
        // Result will be false without real SMTP, but function should execute
    }

    public function testSendEmailWithPlainTextMessage()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'message_plain' => 'Test plain text',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithBccString()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'bcc' => 'bcc1@example.com, bcc2@example.com'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithBccArray()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'bcc' => ['bcc1@example.com', 'bcc2@example.com']
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithEmptyBccInArray()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'bcc' => ['', '  ', 'valid@example.com']
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
        // Empty BCC addresses should be skipped
    }

    public function testSendEmailWithSingleAttachment()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'attachments' => [
                [
                    'filename' => 'test.txt',
                    'content' => base64_encode('Test file content'),
                    'type' => 'text/plain'
                ]
            ]
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithMultipleAttachments()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'attachments' => [
                [
                    'filename' => 'test1.txt',
                    'content' => base64_encode('File 1 content'),
                    'type' => 'text/plain'
                ],
                [
                    'filename' => 'test2.pdf',
                    'content' => base64_encode('File 2 content'),
                    'type' => 'application/pdf'
                ]
            ]
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithAttachmentWithoutType()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'attachments' => [
                [
                    'filename' => 'test.txt',
                    'content' => base64_encode('Test content')
                    // No 'type' field
                ]
            ]
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
        // Should handle missing type gracefully
    }

    public function testSendEmailWithMalformedAttachment()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'attachments' => [
                [
                    'filename' => 'test.txt'
                    // Missing 'content' field - should be skipped
                ]
            ]
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithNonArrayAttachments()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass',
            'attachments' => 'not-an-array'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
        // Should handle non-array attachments gracefully
    }

    public function testSendEmailOutputsErrorOnFailure()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'invalid.smtp.server.that.does.not.exist',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertFalse($result);
        $this->assertStringContainsString('Mailer Error:', $output);
    }

    public function testSendEmailWithUtf8Subject()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'subject' => 'Test Subject with UTF-8: 你好 🎉',
            'message_html' => '<p>Test HTML</p>',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }

    public function testSendEmailWithUtf8Content()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'from_name' => 'Émile Zola',
            'subject' => 'Test Subject',
            'message_html' => '<p>Bonjour! Ça va? 你好！🎉</p>',
            'message_plain' => 'Bonjour! Ça va? 你好！',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_user' => 'testuser',
            'smtp_pass' => 'testpass'
        ];
        
        ob_start();
        $result = send_email($config);
        $output = ob_get_clean();
        
        $this->assertIsBool($result);
    }
}
