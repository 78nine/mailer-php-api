<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // build_mail_config tests
    public function testBuildMailConfigMergesRequestAndIni()
    {
        $request_data = [
            'to_email' => 'request@example.com',
            'subject' => 'Request Subject'
        ];
        
        $ini_config = [
            'to_email' => 'ini@example.com',
            'subject' => 'INI Subject',
            'message_html' => '<p>HTML message</p>',
            'message_plain' => 'Plain message',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'smtp_host' => 'smtp.example.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587'
        ];
        
        $result = build_mail_config($request_data, $ini_config);
        
        // Request data should override INI
        $this->assertEquals('request@example.com', $result['to_email']);
        $this->assertEquals('Request Subject', $result['subject']);
        
        // INI data should be used when not in request
        $this->assertEquals('<p>HTML message</p>', $result['message_html']);
        $this->assertEquals('sender@example.com', $result['from_email']);
    }

    public function testBuildMailConfigWithAllRequiredFields()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test Subject',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'From Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587'
        ];
        
        $result = build_mail_config($request_data, []);
        
        $this->assertEquals('to@example.com', $result['to_email']);
        $this->assertEquals('Test Subject', $result['subject']);
        $this->assertEquals('<p>HTML</p>', $result['message_html']);
        $this->assertEquals('from@example.com', $result['from_email']);
        $this->assertEquals('From Name', $result['from_name']);
        $this->assertEquals('smtp.test.com', $result['smtp_host']);
        $this->assertEquals('user', $result['smtp_user']);
        $this->assertEquals('pass', $result['smtp_pass']);
        $this->assertEquals('587', $result['smtp_port']);
    }

    public function testBuildMailConfigMessagePlainIsOptional()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587'
            // message_plain is not included
        ];
        
        $result = build_mail_config($request_data, []);
        
        $this->assertArrayNotHasKey('message_plain', $result);
    }

    public function testBuildMailConfigIncludesMessagePlainWhenProvided()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'message_plain' => 'Plain text',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587'
        ];
        
        $result = build_mail_config($request_data, []);
        
        $this->assertEquals('Plain text', $result['message_plain']);
    }

    public function testBuildMailConfigTrimsValues()
    {
        $request_data = [
            'to_email' => '  to@example.com  ',
            'subject' => '  Test Subject  '
        ];
        
        $ini_config = [
            'message_html' => '  <p>HTML</p>  ',
            'from_email' => '  from@example.com  ',
            'from_name' => '  Name  ',
            'smtp_host' => '  smtp.test.com  ',
            'smtp_user' => '  user  ',
            'smtp_pass' => '  pass  ',
            'smtp_port' => '  587  '
        ];
        
        $result = build_mail_config($request_data, $ini_config);
        
        $this->assertEquals('to@example.com', $result['to_email']);
        $this->assertEquals('Test Subject', $result['subject']);
        $this->assertEquals('<p>HTML</p>', $result['message_html']);
        $this->assertEquals('from@example.com', $result['from_email']);
    }

    public function testBuildMailConfigHandlesBccFromRequest()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587',
            'bcc' => ['bcc1@example.com', 'bcc2@example.com']
        ];
        
        $result = build_mail_config($request_data, []);
        
        $this->assertArrayHasKey('bcc', $result);
        $this->assertEquals(['bcc1@example.com', 'bcc2@example.com'], $result['bcc']);
    }

    public function testBuildMailConfigHandlesBccFromIni()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587'
        ];
        
        $ini_config = [
            'bcc' => 'bcc@example.com'
        ];
        
        $result = build_mail_config($request_data, $ini_config);
        
        $this->assertArrayHasKey('bcc', $result);
        $this->assertEquals('bcc@example.com', $result['bcc']);
    }

    public function testBuildMailConfigBccFromRequestOverridesIni()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587',
            'bcc' => ['request@example.com']
        ];
        
        $ini_config = [
            'bcc' => 'ini@example.com'
        ];
        
        $result = build_mail_config($request_data, $ini_config);
        
        $this->assertEquals(['request@example.com'], $result['bcc']);
    }

    public function testBuildMailConfigHandlesAttachments()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>HTML</p>',
            'from_email' => 'from@example.com',
            'from_name' => 'Name',
            'smtp_host' => 'smtp.test.com',
            'smtp_user' => 'user',
            'smtp_pass' => 'pass',
            'smtp_port' => '587',
            'attachments' => [
                ['filename' => 'file1.txt', 'content' => 'base64content', 'type' => 'text/plain']
            ]
        ];
        
        $result = build_mail_config($request_data, []);
        
        $this->assertArrayHasKey('attachments', $result);
        $this->assertCount(1, $result['attachments']);
        $this->assertEquals('file1.txt', $result['attachments'][0]['filename']);
    }



    public function testBuildMailConfigWithPartialIniConfig()
    {
        $request_data = [
            'to_email' => 'to@example.com',
            'subject' => 'Test',
            'message_html' => '<p>Test</p>'
        ];
        
        $ini_config = [
            'from_email' => 'default@example.com',
            'from_name' => 'Default Name',
            'smtp_host' => 'smtp.default.com',
            'smtp_user' => 'default_user',
            'smtp_pass' => 'default_pass',
            'smtp_port' => '25'
        ];
        
        $result = build_mail_config($request_data, $ini_config);
        
        $this->assertEquals('to@example.com', $result['to_email']);
        $this->assertEquals('default@example.com', $result['from_email']);
        $this->assertEquals('smtp.default.com', $result['smtp_host']);
    }
}
