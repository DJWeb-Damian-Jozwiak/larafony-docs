---
title: "Sending Emails"
description: "Native SMTP implementation built from scratch with Laravel-inspired Mailable classes"
---

# Sending Emails

> **Info:** **Zero Dependencies:** Complete RFC 5321 compliant SMTP implementation without external libraries. Symfony Mailer support coming after PHP 8.5 GA.

## Overview

Larafony's Mail component provides:

- **Native SMTP** - RFC 5321 compliant implementation from scratch

- **Mailable Classes** - Laravel-inspired email composition

- **Blade Templates** - Use views for email content

- **Interface-Based** - Easy to swap transports via TransportContract

- **Database Logging** - Track sent emails with MailHistoryLogger

- **DSN Configuration** - Connection strings with smart defaults

- **Framework Integration** - Reuses UriManager, Stream, ViewManager

## Quick Start

### 1. Configuration

```php
use Larafony\Framework\Mail\MailerFactory;

// From DSN with smart defaults
$mailer = MailerFactory::fromDsn('smtp://user:pass@smtp.example.com:587');

// MailHog for local development
$mailer = MailerFactory::createMailHogMailer('localhost', 1025);
```

> **Success:** **Development Tip:** Use MailHog to test emails locally without sending real messages. Install with Docker: `docker run -p 1025:1025 -p 8025:8025 mailhog/mailhog`

### 2. Create a Mailable Class

```php
namespace App\Mail;

use Larafony\Framework\Mail\Mailable;
use Larafony\Framework\Mail\Envelope;
use Larafony\Framework\Mail\Content;
use Larafony\Framework\Mail\Address;

class WelcomeEmail extends Mailable
{
public function __construct(
private readonly string $userName,
private readonly string $userEmail
) {}

protected function envelope(): Envelope
{
return (new Envelope())
->from(new Address('noreply@example.com', 'Larafony'))
->to(new Address($this->userEmail))
->subject('Welcome to Larafony!');
}

protected function content(): Content
{
return new Content(
view: 'emails.welcome',
data: ['userName' => $this->userName]
);
}
}
```

### 3. Create Email View

Create `resources/views/emails/welcome.blade.php`:

```blade
@component('components.Layout', ['title' => 'Welcome'])

<h1>Welcome to Larafony!</h1>

Hello **{{ $userName }}**,

Thank you for joining Larafony! We're excited to have you on board.

[
Get Started
](https://github.com/larafony/framework)

@endcomponent
```

### 4. Send the Email

```php
$mailer->send(new WelcomeEmail('John Doe', 'john@example.com'));
```

## DSN Configuration

DSN format: `smtp://[username:password@]host[:port]`

```php
// Basic SMTP (port 25)
$mailer = MailerFactory::fromDsn('smtp://localhost');

// With authentication (port 587 for TLS by default)
$mailer = MailerFactory::fromDsn('smtp://user:pass@smtp.gmail.com:587');

// SSL (port 465)
$mailer = MailerFactory::fromDsn('smtps://user:pass@smtp.gmail.com:465');

// Explicit TLS
$mailer = MailerFactory::fromDsn('smtp+tls://user:pass@smtp.example.com:587');
```

### Smart Port Defaults

- **Port 25** - Plain SMTP (no encryption)

- **Port 587** - SMTP with TLS/STARTTLS

- **Port 465** - SMTP with SSL (smtps://)

## Advanced Usage

### Multiple Recipients

```php
protected function envelope(): Envelope
{
return (new Envelope())
->from(new Address('noreply@example.com', 'Larafony'))
->to(new Address('user1@example.com'))
->to(new Address('user2@example.com'))
->cc(new Address('manager@example.com'))
->bcc(new Address('admin@example.com'))
->replyTo(new Address('support@example.com'))
->subject('Team Update');
}
```

### Email Logging

Track sent emails in the database:

```php
use Larafony\Framework\Mail\MailHistoryLogger;
use Larafony\Framework\Mail\Mailer;

$logger = new MailHistoryLogger();
$mailer = new Mailer($transport, $logger);

// Emails are automatically logged when sent
$mailer->send($mailable);
```

Create the mail_log table:

```bash
php bin/larafony table:mail-log
php bin/larafony migrate
```

## SMTP Protocol Details

### Implemented Commands

- **EHLO** - Extended hello, establishes connection

- **AUTH LOGIN** - Base64-encoded authentication

- **MAIL FROM** - Specifies sender address

- **RCPT TO** - Specifies recipient addresses (to, cc, bcc)

- **DATA** - Begins message content

- **QUIT** - Closes connection

### Response Codes

- **2xx** - Success (250 OK, 220 Ready)

- **3xx** - Intermediate (354 Start mail input)

- **4xx** - Transient failure (421 Service not available)

- **5xx** - Permanent failure (550 Mailbox unavailable)

### Multi-line Response Handling

SMTP responses can span multiple lines. The implementation detects the last line by checking character at index 3:

```text
250-mail.example.com
250-SIZE 52428800
250-8BITMIME
250 HELP
^ Space indicates this is the final line
```

> **Info:** **RFC Compliance:** Our implementation follows RFC 5321 specification for SMTP protocol.

## PHP 8.5 Features

### Asymmetric Visibility

Email and Envelope use `private(set)` for immutability:

```php
final class Email
{
public function __construct(
public private(set) ?Address $from = null,
public private(set) array $to = [],
public private(set) ?string $subject = null,
) {}

// Immutable API using clone()
public function from(Address $address): self
{
return clone($this, ['from' => $address]);
}

public function to(Address $address): self
{
return clone($this, ['to' => [...$this->to, $address]]);
}
}
```

### Property Hooks

Smart defaults with property hooks:

```php
final class MailEncryption
{
public bool $isSsl {
get => $this->value === 'ssl';
}

public bool $isTls {
get => $this->value === 'tls';
}

private function __construct(
public private(set) string $value
) {}
}
```

> **Warning:** **Important Caveat:** Properties with `private(set)` cannot use reference-based operations like `array_walk()`. According to RFC Asymmetric Visibility v2, obtaining a reference follows `set` visibility, not `get` visibility. Use `foreach` for read-only iteration.

## Architecture

### Value Objects

- **Address** - Email address with optional name

- **MailPort** - Smart port selection based on encryption

- **MailEncryption** - SSL, TLS, or none

- **MailUserInfo** - Parses username:password from DSN

- **SmtpCommand** - SMTP commands with validation

- **SmtpResponse** - SMTP response parsing and validation

### Contracts (Interfaces)

```php
interface TransportContract
{
public function send(Email $message): void;
}

interface MailerContract
{
public function send(Mailable $mailable): void;
}

interface MailHistoryLoggerContract
{
public function log(Email $message): void;
}
```

### Framework Integration

- **UriManager** - DSN parsing with scheme detection

- **Stream** - Socket I/O from HTTP module

- **ViewManager** - Email template rendering

- **Application Container** - Dependency injection

- **DBAL Models** - Email logging with ORM

## Testing

### Using MailHog

```bash
# Start MailHog with Docker
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

# View sent emails at http://localhost:8025
```

```php
use Larafony\Framework\Mail\MailerFactory;

$mailer = MailerFactory::createMailHogMailer();
$mailer->send(new WelcomeEmail('Test User', 'test@example.com'));

// Check http://localhost:8025 to see the email
```

## Future Enhancements

After PHP 8.5 GA release and base implementation completion:

- **Symfony Mailer Integration** - Add `symfony/mailer` as optional transport

- **Additional Transports** - AWS SES, SendGrid, Mailgun drivers

- **Attachments** - File attachment support

- **Multipart** - HTML/text multipart messages

- **Inline Images** - Embedded image support

- **Queue Integration** - Async email sending

- **Testing Utilities** - Fake mailer for unit tests

## Resources

- [RFC 5321 - Simple Mail Transfer Protocol](https://datatracker.ietf.org/doc/html/rfc5321)

- [RFC 2045 - MIME Format](https://datatracker.ietf.org/doc/html/rfc2045)

- [PHP RFC: Property Hooks](https://wiki.php.net/rfc/property-hooks)

- [PHP RFC: Asymmetric Visibility v2](https://wiki.php.net/rfc/asymmetric-visibility-v2)

- [MailHog - Email testing tool](https://github.com/mailhog/MailHog)

## Learn More

This native SMTP implementation is explained in detail with RFC compliance, PHP 8.5 features, and production-ready patterns at
[
**masterphp.eu** 
](https://masterphp.eu)

**Zero Dependencies:** Unlike other frameworks that rely on Symfony Mailer or SwiftMailer, Larafony implements SMTP from scratch using only PSR standards and PHP 8.5. This demonstrates complete framework transparency - you can read and understand every line of the mail system without diving into external libraries.

**Coming Soon:** Symfony Mailer integration will be added as an optional transport after PHP 8.5 GA, giving you the choice between native implementation and battle-tested external solutions.

[
View on Packagist
](https://packagist.org/packages/larafony/core)
[
View on GitHub
](https://github.com/larafony/framework)
