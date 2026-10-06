# Mailer - PHP API

Send emails using an HTTP PHP API using PHPMailer.

## Installation

- Just copy the `www` folder to your webserver.
- Make sure to rename `config.template.ini` to `config.ini` and set the fields.
- Make sure `config.ini` is inaccessible through the web, .htaccess does this already for Apache servers.

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

**400 Bad Request** - Email send failure
```text
Mailer Error: SMTP Error: Could not authenticate.
```
*(Note: PHPMailer errors return plain text, not JSON)*

**401 Unauthorized** - Invalid or missing API token
```text
(Empty response body)
```

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
