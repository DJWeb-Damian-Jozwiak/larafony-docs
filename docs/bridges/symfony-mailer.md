---
title: "Symfony Mailer Bridge"
description: "Send emails with Symfony Mailer - SMTP, Amazon SES, Mailgun, SendGrid, and more."
---

# Symfony Mailer Bridge

## Installation

```bash
composer require larafony/mail-symfony

# Optional transports
composer require symfony/amazon-mailer # Amazon SES
composer require symfony/mailgun-mailer # Mailgun
composer require symfony/sendgrid-mailer # SendGrid
composer require symfony/postmark-mailer # Postmark
```

## Configuration

Register the service provider in your `bootstrap.php`:

```php
use Larafony\Mail\Symfony\ServiceProviders\SymfonyMailerServiceProvider;

$app->withServiceProviders([
SymfonyMailerServiceProvider::class
]);
```

Create `config/mail.php`:

```php
use Larafony\Framework\Config\Environment\EnvReader;

return [
'default' => EnvReader::read('MAIL_MAILER', 'smtp'),

'mailers' => [
'smtp' => [
'transport' => 'smtp',
'host' => EnvReader::read('MAIL_HOST', 'localhost'),
'port' => EnvReader::read('MAIL_PORT', 587),
'encryption' => EnvReader::read('MAIL_ENCRYPTION', 'tls'),
'username' => EnvReader::read('MAIL_USERNAME'),
'password' => EnvReader::read('MAIL_PASSWORD'),
],

'ses' => [
'transport' => 'ses',
'region' => EnvReader::read('AWS_DEFAULT_REGION'),
],

'mailgun' => [
'transport' => 'mailgun',
'domain' => EnvReader::read('MAILGUN_DOMAIN'),
'secret' => EnvReader::read('MAILGUN_SECRET'),
],
],

'from' => [
'address' => EnvReader::read('MAIL_FROM_ADDRESS', 'hello@example.com'),
'name' => EnvReader::read('MAIL_FROM_NAME', 'Larafony'),
],
];
```

## Using Mailable Classes

```php
use Larafony\Framework\Mail\Mailable;
use Larafony\Framework\Mail\Envelope;
use Larafony\Framework\Mail\Content;
use Larafony\Framework\Mail\Address;

final class WelcomeMail extends Mailable
{
public function __construct(
private readonly User $user,
private readonly string $activationUrl,
) {}

public function envelope(): Envelope
{
return new Envelope(
from: new Address('noreply@example.com', 'Larafony App'),
subject: 'Welcome to Larafony!',
)->addTo(new Address($this->user->email, $this->user->name));
}

public function content(): Content
{
return new Content(
view: 'emails.welcome',
data: [
'user' => $this->user,
'activationUrl' => $this->activationUrl,
],
);
}
}
```

## Sending Emails

```php
use Larafony\Framework\Mail\Contracts\MailerContract;

final class AuthController extends Controller
{
#[Route('/register', methods: ['POST'])]
public function register(MailerContract $mailer, UserDto $dto): ResponseInterface
{
$user = User::create($dto->toArray());

$mailer->send(new WelcomeMail($user, $activationUrl));

return new ResponseFactory()->createJsonResponse(['registered' => true]);
}
}
```

## With Attachments

```php
use Larafony\Mail\Symfony\EmailConverter;
use Larafony\Mail\Symfony\Transport\SymfonyTransport;
use Symfony\Component\Mime\Email;

#[Route('/invoices/<id:\d+>/send', methods: ['POST'])]
public function sendInvoice(SymfonyTransport $transport, ViewManager $viewManager, int $id): ResponseInterface
{
$invoice = Invoice::find($id);

// Build email using Mailable (renders Blade view)
$mailable = new InvoiceMail($invoice);
$larafonyEmail = $mailable->withViewManager($viewManager)->build();

// Convert to Symfony Email and add attachments
$symfonyEmail = EmailConverter::toSymfony($larafonyEmail)
->attachFromPath(storage_path("invoices/{$invoice->number}.pdf"))
->priority(Email::PRIORITY_HIGH);

$transport->sendSymfonyEmail($symfonyEmail);

return new ResponseFactory()->createJsonResponse(['sent' => true]);
}
```

## Features

- **Multiple transports** - SMTP, Amazon SES, Mailgun, Postmark, SendGrid

- **Mailable classes** - Declarative email building with Blade views

- **Attachments** - Files, inline images, raw content

- **HTML & Text** - Both formats in single email

- **EmailConverter** - Convert Larafony Email to Symfony Email

- **DSN configuration** - Simple transport configuration
