---
title: "Sessions & Cookies"
description: "Encrypted session management with file and database storage, plus secure cookie handling"
---

# Sessions & Cookies

> **Info:** **Automatic Encryption:** All session data and cookies are automatically encrypted using XChaCha20-Poly1305 AEAD cipher. No middleware configuration needed.

## Overview

Larafony's session and cookie system provides:

- **Automatic Encryption** - All data encrypted with XChaCha20-Poly1305

- **Dual Storage** - File-based and database-backed session handlers

- **PSR-20 Clock Integration** - Testable time-based operations

- **Secure Defaults** - HttpOnly, Secure (HTTPS), SameSite protection

- **SessionHandlerInterface** - Standard PHP session handler implementation

- **Transparent API** - Simple get/set interface, encryption happens automatically

## Session Management

### Basic Session Usage

```php
use Larafony\Framework\Storage\Session\SessionManager;

// Create and start session (auto-selects handler from config)
$session = SessionManager::create();

// Store data (automatically encrypted)
$session->set('user_id', 42);
$session->set('cart', ['item1', 'item2', 'item3']);
$session->set('preferences', [
'theme' => 'dark',
'language' => 'en'
]);

// Retrieve data (automatically decrypted)
$userId = $session->get('user_id'); // 42
$cart = $session->get('cart', []); // ['item1', 'item2', 'item3']

// Check existence
if ($session->has('user_id')) {
// User is logged in
}

// Get all session data
$allData = $session->all();

// Remove specific item
$session->remove('cart');

// Clear all session data
$session->clear();

// Get session ID
$sessionId = $session->getId();
```

### Session Security

```php
// Regenerate session ID (best practice after login)
$session->regenerateId(deleteOldSession: true);

// Destroy session (logout)
$session->destroy();
```

> **Success:** **Security Best Practice:** Always regenerate the session ID after authentication to prevent session fixation attacks.

## Session Storage Handlers

### File-Based Sessions

Default handler, stores encrypted session data as files:

```php
// config/session.php
return [
'handler' => \Larafony\Framework\Storage\Session\Handlers\FileSessionHandler::class,
'path' => sys_get_temp_dir() . '/sessions',
// or: storage_path('framework/sessions')
];
```

**Features:**

- ✅ Simple, no database required

- ✅ Automatic garbage collection of expired sessions

- ✅ Good for single-server applications

- ❌ Not suitable for load-balanced environments

### Database-Backed Sessions

Recommended for production and multi-server setups:

```php
// config/session.php
return [
'handler' => \Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler::class,
];
```

**Features:**

- ✅ Scalable - works with load balancers

- ✅ Tracks user IP, user agent, user ID

- ✅ Automatic expiration checking

- ✅ ORM integration with property hooks

- ✅ Indexed queries for performance

### Setup Database Sessions

**Step 1:** Generate migration

```bash
php bin/console table:session
```

**Step 2:** Run migration

```bash
php bin/console migrate
```

**Step 3:** Configure handler in `config/session.php`

```php
return [
'handler' => \Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler::class,

'cookie_params' => [
'lifetime' => 7200, // 2 hours
'path' => '/',
'domain' => '',
'secure' => true, // HTTPS only
'httponly' => true, // No JavaScript access
'samesite' => 'Strict', // CSRF protection
],
];
```

### Database Schema

The sessions table stores:

```sql
CREATE TABLE sessions (
id VARCHAR(255) PRIMARY KEY, -- Session ID
payload TEXT NOT NULL, -- Encrypted session data
last_activity INT NOT NULL, -- Unix timestamp
user_ip VARCHAR(45) NULL, -- IPv4 or IPv6
user_agent TEXT NULL, -- Browser info
user_id BIGINT UNSIGNED NULL, -- Linked user (if authenticated)
INDEX idx_last_activity (last_activity),
INDEX idx_user_id (user_id)
);
```

## Cookie Management

### Basic Cookie Usage

```php
use Larafony\Framework\Storage\CookieManager;
use Larafony\Framework\Storage\CookieOptions;

$cookies = new CookieManager();

// Set encrypted cookie with defaults
$cookies->set('user_preferences', [
'theme' => 'dark',
'language' => 'en',
'notifications' => true
]);

// Retrieve and decrypt automatically
$preferences = $cookies->get('user_preferences');
// Returns: ['theme' => 'dark', 'language' => 'en', 'notifications' => true]

// Get with default value
$settings = $cookies->get('settings', ['default' => 'value']);

// Get all cookies (decrypted)
$allCookies = $cookies->all();

// Remove cookie
$cookies->remove('user_preferences');
```

### Cookie Options

```php
use Larafony\Framework\Storage\CookieOptions;

// Custom cookie options
$options = new CookieOptions(
expires: time() + 86400, // 24 hours from now
path: '/admin', // Available only under /admin
domain: '.example.com', // Available on all subdomains
secure: true, // HTTPS only
httponly: true, // No JavaScript access
samesite: 'Strict' // Strict CSRF protection
);

$cookies->set('admin_token', 'secret_value', $options);
```

### Secure Defaults

CookieOptions provides secure defaults automatically:

```php
// Default options (without parameters):
new CookieOptions()
// Equivalent to:
new CookieOptions(
expires: time() + 3600, // 1 hour
path: '/',
domain: '',
secure: (HTTPS detected), // Auto-detects HTTPS
httponly: true, // Always true by default
samesite: 'Lax' // Balanced security
)
```

> **Warning:** **Production Tip:** Always use `secure: true` and `samesite: 'Strict'` in production to maximize security.

## Session Configuration

### Complete Configuration Example

```php
// config/session.php
use Larafony\Framework\Config\Environment\EnvReader;
use Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;

return [
// Session handler: file or database
'handler' => EnvReader::read('SESSION_DRIVER') === 'database'
? DatabaseSessionHandler::class
: FileSessionHandler::class,

// File storage path (for FileSessionHandler)
'path' => EnvReader::read('SESSION_PATH', sys_get_temp_dir() . '/sessions'),

// Cookie configuration
'cookie_params' => [
'lifetime' => (int) EnvReader::read('SESSION_LIFETIME', '7200'),
'path' => EnvReader::read('SESSION_PATH_COOKIE', '/'),
'domain' => EnvReader::read('SESSION_DOMAIN', ''),
'secure' => EnvReader::read('SESSION_SECURE_COOKIE') === 'true'
|| (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
'httponly' => EnvReader::read('SESSION_HTTP_ONLY', 'true') === 'true',
'samesite' => EnvReader::read('SESSION_SAME_SITE', 'Lax'),
],
];
```

### Environment Variables

```bash
# .env file

# Session driver: file or database
SESSION_DRIVER=database

# Session lifetime (seconds)
SESSION_LIFETIME=7200

# File storage path (if using file driver)
SESSION_PATH=/var/www/storage/sessions

# Cookie settings
SESSION_PATH_COOKIE=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Strict
```

## How Encryption Works

### Session Encryption

Sessions are encrypted at the storage layer:

```php
// SessionSecurity class (used by both handlers)
class SessionSecurity
{
public function encrypt(string $data): string
{
$encryptor = new EncryptionService();
return $encryptor->encrypt($data);
}

public function decrypt(string $encrypted): string
{
$encryptor = new EncryptionService();
return $encryptor->decrypt($encrypted);
}
}

// FileSessionHandler writes encrypted data
public function write(string $id, string $data): bool
{
$encrypted = $this->security->encrypt($data);
file_put_contents($this->getFilePath($id), $encrypted);
return true;
}

// DatabaseSessionHandler writes encrypted data
public function write(string $id, string $data): bool
{
$encrypted = $this->security->encrypt($data);
$session->payload = $encrypted;
$session->save();
return true;
}
```

### Cookie Encryption

Cookies are transparently encrypted/decrypted:

```php
// CookieManager automatically encrypts
public function set(string $name, mixed $value, CookieOptions $options): void
{
$encrypted = new EncryptionService()->encrypt($value);
setcookie($name, $encrypted, $options->toArray());
}

// CookieManager automatically decrypts
public function get(string $name, mixed $default = null): mixed
{
$value = $_COOKIE[$name] ?? null;

if ($value === null) {
return $default;
}

return new EncryptionService()->decrypt($value);
}
```

## Comparison with Other Frameworks

<table class="table table-dark table-striped">
<thead>
<tr>
<th>Feature</th>
<th>Larafony</th>
<th>Laravel</th>
<th>Symfony
</thead>
<tbody>
<tr>
<td>Session Encryption</td>
<td>Automatic</td>
<td>Optional (middleware)</td>
<td>Manual (bundles)
<tr>
<td>Cookie Encryption</td>
<td>Automatic</td>
<td>Middleware required</td>
<td>Manual (bundles)
<tr>
<td>Storage Handlers</td>
<td>File, Database</td>
<td>File, DB, Redis, Array</td>
<td>File, PDO, Redis, Memcached
<tr>
<td>Configuration</td>
<td>Simple config file</td>
<td>.env + config files</td>
<td>YAML/PHP config
<tr>
<td>Cipher</td>
<td>XChaCha20-Poly1305</td>
<td>AES-256-CBC + HMAC</td>
<td>Varies
<tr>
<td>Clock Integration</td>
<td>PSR-20</td>
<td>Carbon (not PSR)</td>
<td>DateTime/Clock

## Use Cases

### User Authentication

```php
// Login
public function login(User $user): void
{
$session = SessionManager::create();

// Regenerate ID to prevent session fixation
$session->regenerateId(deleteOldSession: true);

// Store user data
$session->set('user_id', $user->id);
$session->set('user_role', $user->role);
$session->set('authenticated_at', time());
}

// Check authentication
public function isAuthenticated(): bool
{
$session = SessionManager::create();
return $session->has('user_id');
}

// Logout
public function logout(): void
{
$session = SessionManager::create();
$session->destroy();
}
```

### Shopping Cart

```php
class CartManager
{
private SessionManager $session;

public function __construct()
{
$this->session = SessionManager::create();
}

public function addItem(int $productId, int $quantity): void
{
$cart = $this->session->get('cart', []);
$cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
$this->session->set('cart', $cart);
}

public function getItems(): array
{
return $this->session->get('cart', []);
}

public function clear(): void
{
$this->session->remove('cart');
}
}
```

### Remember Me Cookie

```php
use Larafony\Framework\Storage\CookieManager;
use Larafony\Framework\Storage\CookieOptions;

// Set remember me token (30 days)
public function setRememberToken(User $user): void
{
$token = bin2hex(random_bytes(32));

// Store in database
$user->remember_token = hash('sha256', $token);
$user->save();

// Store in encrypted cookie
$cookies = new CookieManager();
$cookies->set('remember_token', $token, new CookieOptions(
expires: time() + (30 * 86400), // 30 days
secure: true,
httponly: true,
samesite: 'Strict'
));
}

// Check remember me token
public function checkRememberToken(): ?User
{
$cookies = new CookieManager();
$token = $cookies->get('remember_token');

if (!$token) {
return null;
}

$hashedToken = hash('sha256', $token);
return User::query()
->where('remember_token', '=', $hashedToken)
->first();
}
```

## Best Practices

> **Success:** **Do's:** ✅ Always regenerate session ID after login ✅ Use database sessions for load-balanced environments ✅ Set appropriate session lifetime (2-8 hours) ✅ Use `secure: true` in production (HTTPS) ✅ Use `samesite: 'Strict'` for sensitive operations ✅ Clean up sessions on logout with `destroy()`

> **Danger:** **Don'ts:** ❌ Don't store sensitive data in cookies (use sessions instead) ❌ Don't use file sessions in load-balanced environments ❌ Don't set session lifetime too long (security risk) ❌ Don't forget to call `regenerateId()` after login ❌ Don't disable `httponly` flag (XSS protection)

## Next Steps

- [Encryption](/docs/encryption) - Learn about the underlying encryption system

- [Configuration](/docs/config) - Configure session and cookie settings

- [Middleware](/docs/middleware) - Create custom session middleware

- [Models & ORM](/docs/models) - Store session data in database with ORM

## Learn More

This implementation is explained in detail with step-by-step tutorials, tests, and best practices at
[
**masterphp.eu** 
](https://masterphp.eu)

**Demo App:** See encrypted sessions and cookies in production with file and database storage handlers. The demo application showcases automatic encryption, PSR-20 clock integration, and secure cookie defaults with HttpOnly and SameSite protection.

[
View on Packagist
](https://packagist.org/packages/larafony/skeleton)
[
View on GitHub
](https://github.com/DJWeb-Damian-Jozwiak/larafony-demo-app)
