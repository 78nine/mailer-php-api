<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // validate_email tests
    public function testValidateEmailWithValidEmail()
    {
        $this->assertTrue(validate_email('test@example.com'));
    }

    public function testValidateEmailWithValidComplexEmail()
    {
        $this->assertTrue(validate_email('user.name+tag@example.co.uk'));
    }

    public function testValidateEmailWithInvalidEmail()
    {
        $this->assertFalse(validate_email('invalid-email'));
    }

    public function testValidateEmailWithEmptyString()
    {
        $this->assertFalse(validate_email(''));
    }

    public function testValidateEmailWithWhitespace()
    {
        $this->assertFalse(validate_email('   '));
    }

    public function testValidateEmailTrimsWhitespace()
    {
        $this->assertTrue(validate_email('  test@example.com  '));
    }

    public function testValidateEmailWithMissingAtSign()
    {
        $this->assertFalse(validate_email('testexample.com'));
    }

    public function testValidateEmailWithMultipleAtSigns()
    {
        $this->assertFalse(validate_email('test@@example.com'));
    }

    // validate_email_addresses tests
    public function testValidateEmailAddressesWithValidEmails()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com'
        ];
        
        // Should not throw or exit
        ob_start();
        validate_email_addresses($config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }



    public function testValidateEmailAddressesWithValidBccArray()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'bcc' => ['bcc1@example.com', 'bcc2@example.com']
        ];
        
        ob_start();
        validate_email_addresses($config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testValidateEmailAddressesWithValidBccString()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'bcc' => 'bcc1@example.com, bcc2@example.com'
        ];
        
        ob_start();
        validate_email_addresses($config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }



    public function testValidateEmailAddressesWithEmptyBcc()
    {
        $config = [
            'to_email' => 'recipient@example.com',
            'from_email' => 'sender@example.com',
            'bcc' => ['', '  ']
        ];
        
        ob_start();
        validate_email_addresses($config);
        $output = ob_get_clean();
        $this->assertEmpty($output); // Empty BCC addresses should be ignored
    }

    // validate_attachment_sizes tests
    public function testValidateAttachmentSizesWithNoAttachments()
    {
        $mail_config = [
            'to_email' => 'test@example.com'
        ];
        $config = [];
        
        ob_start();
        validate_attachment_sizes($mail_config, $config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testValidateAttachmentSizesWithValidAttachment()
    {
        // Create a small base64 encoded string (simulating 1KB file)
        $content = base64_encode(str_repeat('x', 1024));
        
        $mail_config = [
            'attachments' => [
                ['content' => $content, 'filename' => 'test.txt']
            ]
        ];
        $config = ['max_attachment_size' => 10485760]; // 10MB
        
        ob_start();
        validate_attachment_sizes($mail_config, $config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }



    public function testValidateAttachmentSizesUsesDefaults()
    {
        $content = base64_encode(str_repeat('x', 1024));
        
        $mail_config = [
            'attachments' => [
                ['content' => $content, 'filename' => 'test.txt']
            ]
        ];
        $config = []; // No limits specified, should use defaults
        
        ob_start();
        validate_attachment_sizes($mail_config, $config);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testValidateAttachmentSizesWithAttachmentWithoutContent()
    {
        $mail_config = [
            'attachments' => [
                ['filename' => 'test.txt'] // No content field
            ]
        ];
        $config = [];
        
        ob_start();
        validate_attachment_sizes($mail_config, $config);
        $output = ob_get_clean();
        $this->assertEmpty($output); // Should skip attachments without content
    }
}
