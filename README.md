# Mailer - PHP API

Send emails using an HTTP PHP API using PHPMailer.

## Installation

- Just copy the `www` folder to your webserver.
- Make sure to rename `config.template.ini` to `config.ini` and set the fields.
- Make sure `config.ini` is inaccessible through the web, .htaccess does this already for Apache servers.

## Configuration

### Rate Limiting

The API includes built-in rate limiting to prevent abuse. Configure in `config.ini`:

```ini
[general]
rate_limit_enabled = true
rate_limit_max = 10        ; Maximum requests
rate_limit_window = 60     ; Time window in seconds
```

**Default**: 10 requests per 60 seconds per IP address.

Rate limiting can be disabled by setting `rate_limit_enabled = false`.

## API Reference

### Request Format

**Endpoint**: `POST /mailer/`

**Headers**:
- `Content-Type: application/json`
- `Authorization: Bearer YOUR_API_TOKEN`

**Required Fields** (if not set in `config.ini`):
- `to_email` - Recipient email address
- `subject` - Email subject line
- `message_html` - HTML email body
- `from_email` - Sender email address
- `from_name` - Sender name
- `smtp_host` - SMTP server hostname
- `smtp_user` - SMTP username
- `smtp_pass` - SMTP password
- `smtp_port` - SMTP port (typically 587 for TLS)

**Optional Fields**:
- `message_plain` - Plain text alternative body
- `bcc` - BCC recipient(s). Can be:
  - Single email: `"bcc@example.com"`
  - Multiple emails (string): `"bcc1@example.com, bcc2@example.com"`
  - Multiple emails (array): `["bcc1@example.com", "bcc2@example.com"]`
- `attachments` - Array of attachment objects (see examples below)

**Attachment Object Format**:
```json
{
  "filename": "document.pdf",
  "content": "base64-encoded-content",
  "type": "application/pdf"
}
```

### Response Codes & Messages

#### Success Responses

**200 OK** - Email sent successfully
```json
{
  "success": true,
  "message": "Email sent successfully"
}
```

#### Error Responses

**400 Bad Request** - Invalid JSON
```json
{
  "error": "Invalid JSON: Syntax error"
}
```

**400 Bad Request** - Missing required fields
```json
{
  "error": "Missing fields: to_email, subject"
}
```

**400 Bad Request** - Invalid email address
```json
{
  "error": "Invalid recipient email address"
}
```
or
```json
{
  "error": "Invalid sender email address"
}
```
or
```json
{
  "error": "Invalid BCC email address: invalid@"
}
```

**400 Bad Request** - Email send failure
```text
Mailer Error: SMTP Error: Could not authenticate.
```
*(Note: PHPMailer errors return plain text, not JSON)*

**401 Unauthorized** - Invalid or missing API token
```text
(Empty response body)
```

**405 Method Not Allowed** - Non-POST request
```json
{
  "error": "Method not allowed. Use POST."
}
```

**429 Too Many Requests** - Rate limit exceeded
```json
{
  "error": "Too many requests. Please try again later.",
  "retry_after": 45
}
```
Headers: `Retry-After: 45`

**500 Internal Server Error** - Server configuration error
```text
(Empty response body)
```
Causes:
- `config.ini` file missing or unreadable
- `api_token` not configured in `config.ini`

## Usage examples

### PHP
```php
$data = [
    'to_email' => 'john.doe@example.com',
    'subject' => 'Test email',
    'message_html' => '<h1>HTML message body</h1>',
    'message_plain' => 'Plain text alternative',
];

$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer API_TOKEN_GOES_HERE',
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => json_encode($data),
    ],
]);

$response = file_get_contents('https://example.com/mailer/', FALSE, $context);

if ($response === FALSE) {
    die('Error: Unable to send the request.');
}

$result = json_decode($response, true);
print_r($result);
```

### JavaScript

```javascript
const data = {
  to_email: 'john.doe@example.com',
  subject: 'Test email',
  message_html: '<h1>HTML message body</h1>',
  message_plain: 'Plain text alternative'
};

const options = {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer API_TOKEN_GOES_HERE'
  },
  body: JSON.stringify(data)
};

fetch('https://example.com/mailer/', options)
  .then(response => response.json())
  .then(result => console.log(result))
  .catch(err => console.error(err));
```

### Python

```python
import http.client
import json

conn = http.client.HTTPSConnection("example.com")

data = {
    'to_email': 'john.doe@example.com',
    'subject': 'Test email',
    'message_html': '<h1>HTML message body</h1>',
    'message_plain': 'Plain text alternative'
}

payload = json.dumps(data)

headers = {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer API_TOKEN_GOES_HERE'
}

conn.request("POST", "/mailer/", payload, headers)

res = conn.getresponse()
data = res.read()

print(json.loads(data.decode("utf-8")))
```

### With Attachments

```javascript
// Read file and convert to base64
const fileContent = btoa(fileData); // or use FileReader API

const data = {
  to_email: 'john.doe@example.com',
  subject: 'Email with attachments',
  message_html: '<p>See attached files</p>',
  attachments: [
    {
      filename: 'document.pdf',
      content: fileContent,  // base64 encoded
      type: 'application/pdf'
    },
    {
      filename: 'image.jpg',
      content: imageBase64,
      type: 'image/jpeg'
    }
  ]
};

fetch('https://example.com/mailer/', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer API_TOKEN_GOES_HERE'
  },
  body: JSON.stringify(data)
})
  .then(response => response.json())
  .then(result => console.log(result));
```

### With BCC Recipients

```javascript
// Single BCC recipient
const data1 = {
  to_email: 'recipient@example.com',
  subject: 'Email with BCC',
  message_html: '<p>Main recipient sees this</p>',
  bcc: 'hidden@example.com'
};

// Multiple BCC recipients (string format)
const data2 = {
  to_email: 'recipient@example.com',
  subject: 'Email with multiple BCCs',
  message_html: '<p>Main recipient sees this</p>',
  bcc: 'bcc1@example.com, bcc2@example.com, bcc3@example.com'
};

// Multiple BCC recipients (array format)
const data3 = {
  to_email: 'recipient@example.com',
  subject: 'Email with multiple BCCs',
  message_html: '<p>Main recipient sees this</p>',
  bcc: ['bcc1@example.com', 'bcc2@example.com', 'bcc3@example.com']
};

fetch('https://example.com/mailer/', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer API_TOKEN_GOES_HERE'
  },
  body: JSON.stringify(data3)
})
  .then(response => response.json())
  .then(result => console.log(result));
```

**Note**: BCC recipients are hidden from the main recipient and from each other. You can also set a default BCC in `config.ini`:

```ini
bcc = archive@example.com, admin@example.com
```

## Response Handling

### Successful Send
```json
{
  "success": true,
  "message": "Email sent successfully"
}
```

### Error Examples

**Missing Fields**:
```json
{
  "error": "Missing fields: to_email, subject"
}
```

**Invalid JSON**:
```json
{
  "error": "Invalid JSON: Syntax error"
}
```

**SMTP/Mailer Error** (plain text):
```
Mailer Error: SMTP Error: Could not authenticate.
```

### Full Error Handling Example (JavaScript)

```javascript
fetch('https://example.com/mailer/', options)
  .then(async response => {
    const isJson = response.headers.get('content-type')?.includes('application/json');
    const data = isJson ? await response.json() : await response.text();
    
    if (!response.ok) {
      throw new Error(isJson ? data.error : data);
    }
    
    return data;
  })
  .then(result => console.log('Success:', result))
  .catch(err => console.error('Error:', err.message));
```

## UTF-8 Support

The API fully supports UTF-8 content:

- ✓ JSON request body (UTF-8 by default)
- ✓ PHPMailer configured with UTF-8 charset
- ✓ JSON responses with UTF-8 charset header

### UTF-8 Example

```javascript
const data = {
  to_email: 'recipient@example.com',
  subject: '🎉 Welcome! Bienvenue! 欢迎!',
  message_html: `
    <h1>Hello World! 👋</h1>
    <p>Testing various scripts:</p>
    <ul>
      <li>English: Hello</li>
      <li>French: Bonjour, café, naïve</li>
      <li>Spanish: Hola, ¿Qué tal?</li>
      <li>German: Guten Tag, Björk</li>
      <li>Chinese: 你好世界</li>
      <li>Arabic: مرحبا بالعالم</li>
      <li>Russian: Привет мир</li>
      <li>Emoji: 😀 ❤️ 🎉 ✨ 🚀</li>
    </ul>
  `,
  message_plain: 'Hello World! 你好世界 مرحبا Привет 😀'
};

fetch('https://example.com/mailer/', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json; charset=utf-8',
    'Authorization': 'Bearer API_TOKEN_GOES_HERE'
  },
  body: JSON.stringify(data)
})
  .then(response => response.json())
  .then(result => console.log(result));
```

All characters will be properly encoded and displayed in email clients.
