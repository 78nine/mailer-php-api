# Code Structure

The mailer API has been refactored into modular components for better readability and maintainability.

## File Organization

```
www/
├── index.php           # Main entry point (orchestrator)
├── bootstrap.php       # Initialization, headers, CORS setup
├── security.php        # Authentication & rate limiting
├── request.php         # Request parsing & config building
├── validation.php      # Email & attachment validation
├── email.php           # Email sending logic
├── response.php        # Response helpers
├── auth.php            # Bearer token extraction
├── rate_limit.php      # Rate limiting implementation
├── config.ini          # Configuration (not in repo)
└── PHPMailer/          # Email library
```

## Module Responsibilities

### `index.php` (37 lines)
**Role**: Orchestrator - coordinates the request flow

**Flow**:
1. Load dependencies
2. Initialize (config, CORS, method check)
3. Authenticate
4. Rate limiting
5. Parse request
6. Validate
7. Send email
8. Respond

### `bootstrap.php`
**Functions**:
- `load_config()` - Load and validate config.ini
- `setup_cors()` - Handle CORS headers and OPTIONS requests
- `enforce_post_method()` - Restrict to POST only

**Responsibilities**:
- Set security headers
- Configure CORS
- Validate HTTP method

### `security.php`
**Functions**:
- `check_authentication()` - Verify bearer token
- `enforce_rate_limit()` - Apply rate limiting

**Responsibilities**:
- Authentication
- Rate limiting enforcement
- Trigger cleanup

### `request.php`
**Functions**:
- `parse_json_request()` - Parse and validate JSON
- `build_mail_config()` - Merge request + config data

**Responsibilities**:
- JSON parsing
- Field mapping
- Required field validation

### `validation.php`
**Functions**:
- `validate_email()` - RFC email validation
- `validate_email_addresses()` - Validate to/from/BCC
- `validate_attachment_sizes()` - Check size limits

**Responsibilities**:
- Email format validation
- Attachment size validation
- Return appropriate errors

### `email.php`
**Functions**:
- `send_email()` - Configure and send via PHPMailer

**Responsibilities**:
- PHPMailer configuration
- SMTP setup
- Attachment handling
- Actual email sending

### `response.php`
**Functions**:
- `send_success_response()` - Return 200 + JSON
- `send_error_response()` - Return 400

**Responsibilities**:
- Standardized responses
- HTTP status codes

### `auth.php`
**Functions**:
- `get_auth_header()` - Extract Authorization header
- `get_bearer_token()` - Parse Bearer token

**Responsibilities**:
- Token extraction from headers

### `rate_limit.php`
**Functions**:
- `get_client_ip()` - Determine client IP
- `check_rate_limit()` - Check/update rate limits
- `cleanup_rate_limit_files()` - Remove old data

**Responsibilities**:
- IP-based rate limiting
- File storage management

## Benefits of This Structure

### Maintainability
✓ Each module has a single responsibility
✓ Changes are isolated to specific files
✓ Easy to locate functionality

### Readability
✓ index.php is now ~37 lines (was 280+)
✓ Clear flow in main entry point
✓ Self-documenting function names

### Testability
✓ Functions can be unit tested individually
✓ Dependencies are explicit
✓ Mocking is straightforward

### Extensibility
✓ Easy to add new validation rules
✓ Simple to swap email providers
✓ Additional security checks are modular

## Request Flow Diagram

```
Client Request
      ↓
[bootstrap.php] → Headers, CORS, Method check
      ↓
[security.php]  → Authentication, Rate limiting
      ↓
[request.php]   → Parse JSON, Build config
      ↓
[validation.php]→ Validate emails & attachments
      ↓
[email.php]     → Send via PHPMailer
      ↓
[response.php]  → JSON response
      ↓
Client Response
```

## Migration Notes

- Old `index.php` backed up as `index.php.backup`
- All functionality preserved
- No configuration changes needed
- API behavior unchanged
