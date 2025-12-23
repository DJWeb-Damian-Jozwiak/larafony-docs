---
title: "Encryption"
description: "Modern encryption using libsodium\'s XChaCha20-Poly1305 AEAD cipher for maximum security"
---

# Encryption

> **Info:** **Modern Cryptography:** Uses XChaCha20-Poly1305 AEAD cipher from libsodium, providing both encryption and authentication in a single operation.

## Overview

Larafony's encryption system provides:

- **XChaCha20-Poly1305** - Modern AEAD (Authenticated Encryption with Associated Data) cipher

- **libsodium** - Industry-standard cryptographic library

- **Automatic Authentication** - Prevents tampering with encrypted data

- **Constraint-Driven Validation** - Five specialized assertion classes for safe error handling

- **Sensitive Parameter Protection** - Uses `#[SensitiveParameter]` to redact values from stack traces

> **Warning:** **vs Laravel:** Laravel uses AES-256-CBC with OpenSSL + separate HMAC. Larafony uses XChaCha20-Poly1305, which is faster, more secure against timing attacks, and combines encryption + authentication in one operation.

## Key Generation

### Generate Encryption Key (CLI)

The easiest way to generate a secure encryption key:

```bash
php bin/console key:generate
```

This command automatically generates a cryptographically secure 32-byte key using libsodium,
encodes it as base64, and stores it in your `.env` file as `APP_KEY`.

> **Success:** **Recommended:** Use the `key:generate` command for production. It automatically handles key generation and .env file updates safely.

### Generate Key Programmatically

If you need to generate keys in code (for testing or custom scenarios):

```php
use Larafony\Framework\Encryption\KeyGenerator;

$generator = new KeyGenerator();
$key = $generator->generateKey();

// Returns base64-encoded 32-byte key
// Example: base64:Hj3kL9mN2pQ5rS8tU0vW1xY4zA6bC7dE9fG0hI2jK3l=
```

### .env File Format

```bash
# .env file (automatically set by key:generate)
APP_KEY=base64:Hj3kL9mN2pQ5rS8tU0vW1xY4zA6bC7dE9fG0hI2jK3l=
```

> **Danger:** **Security Warning:** Never commit your `APP_KEY` to version control. Keep it secret and rotate it periodically in production.

## Basic Encryption

### Encrypt Data

```php
use Larafony\Framework\Encryption\EncryptionService;

$encryptor = new EncryptionService();

// Encrypt any value (strings, arrays, objects)
$encrypted = $encryptor->encrypt('secret message');

// Encrypt complex data
$encrypted = $encryptor->encrypt([
'user_id' => 123,
'api_token' => 'abc123xyz',
'expires_at' => '2025-12-31'
]);
```

### Decrypt Data

```php
use Larafony\Framework\Encryption\EncryptionService;

$encryptor = new EncryptionService();

try {
$decrypted = $encryptor->decrypt($encrypted);
// Returns original value
} catch (\InvalidArgumentException $e) {
// Decryption failed - data corrupted or wrong key
// Possible errors:
// - Invalid base64 encoding
// - Data too short
// - Decryption failed (tampered data)
}
```

## How It Works

### Encryption Process

- **Key Validation** - Validates APP_KEY exists and is 32 bytes when decoded

- **Serialization** - Converts value to string using PHP's serialize()

- **Nonce Generation** - Creates random 24-byte nonce for this encryption

- **AEAD Encryption** - Uses XChaCha20-Poly1305 to encrypt + authenticate in one step

- **Combine & Encode** - Merges nonce + ciphertext, encodes as base64

```php
// Internal process (simplified):
$nonce = random_bytes(24); // Unique per encryption
$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
serialize($value),
'', // No additional data
$nonce,
$this->key
);
return base64_encode($nonce . $ciphertext);
```

### Decryption Process

- **Base64 Decode** - Converts base64 string to binary

- **Extract Nonce** - First 24 bytes are the nonce

- **Extract Ciphertext** - Remaining bytes are encrypted data

- **AEAD Decryption** - Decrypts and verifies authentication tag

- **Unserialize** - Converts string back to original PHP value

## Constraint Assertions

Larafony uses specialized assertion classes following Single Responsibility Principle. Each validates one specific constraint:

### Available Assertions

```php
use Larafony\Framework\Encryption\Assert\EncryptionKeyExists;
use Larafony\Framework\Encryption\Assert\KeyLengthIsValid;
use Larafony\Framework\Encryption\Assert\Base64IsValid;
use Larafony\Framework\Encryption\Assert\DataLengthIsValid;
use Larafony\Framework\Encryption\Assert\DecryptionSucceeded;

// 1. Validates encryption key exists
EncryptionKeyExists::assert($key);
// Throws: RuntimeException if null

// 2. Validates key length (32 bytes for XChaCha20)
KeyLengthIsValid::assert($decodedKey, 32);
// Throws: InvalidArgumentException if wrong length

// 3. Validates base64 decoding
$decoded = base64_decode($encrypted, true);
Base64IsValid::assert($decoded);
// Throws: InvalidArgumentException if invalid base64

// 4. Validates minimum data length
DataLengthIsValid::assert($decoded, 24);
// Throws: InvalidArgumentException if too short

// 5. Validates decryption success
$decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(...);
DecryptionSucceeded::assert($decrypted);
// Throws: InvalidArgumentException if decryption failed
```

### PHPStan Support

Assertion classes include `@phpstan-assert` annotations for static analysis type narrowing:

```php
/**
 * @param string|false $decoded
 * @phpstan-assert string $decoded
 */
public static function assert(string|false $decoded): void
{
if ($decoded === false) {
throw new InvalidArgumentException('Invalid base64 encoding');
}
}

// After this assertion, PHPStan knows $decoded is string, not string|false
```

## Security Features

### Sensitive Parameter Protection

The `encrypt()` method uses PHP 8.2+ `#[SensitiveParameter]` attribute:

```php
public function encrypt(#[\SensitiveParameter] mixed $value): string
{
// If exception occurs, $value won't appear in stack traces
}
```

### AEAD: Encryption + Authentication

Unlike older ciphers that require separate HMAC, XChaCha20-Poly1305 provides:

- **Confidentiality** - Data is encrypted and unreadable

- **Authentication** - Detects any tampering with encrypted data

- **Performance** - Single operation is faster than encrypt + HMAC

- **Timing Safety** - Resistant to timing side-channel attacks

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
<td>Cipher</td>
<td>XChaCha20-Poly1305</td>
<td>AES-256-CBC</td>
<td>Varies (bundles)
<tr>
<td>Library</td>
<td>libsodium</td>
<td>OpenSSL</td>
<td>libsodium (secrets)
<tr>
<td>Authentication</td>
<td>Built-in (AEAD)</td>
<td>Separate HMAC</td>
<td>Varies
<tr>
<td>Performance</td>
<td>Fast (single op)</td>
<td>Slower (two ops)</td>
<td>Varies
<tr>
<td>Timing Attacks</td>
<td>Resistant</td>
<td>More vulnerable</td>
<td>Varies
<tr>
<td>Key Management</td>
<td>Symmetric (APP_KEY)</td>
<td>Symmetric (APP_KEY)</td>
<td>Asymmetric (pub/priv)

## Use Cases

### Encrypt Sensitive Configuration

```php
// Store encrypted API credentials
$encryptor = new EncryptionService();

$credentials = [
'api_key' => 'sk_live_abc123...',
'api_secret' => 'secret_xyz789...',
'webhook_secret' => 'whsec_...'
];

$encrypted = $encryptor->encrypt($credentials);

// Store $encrypted in database or config file
// Decrypt when needed:
$decrypted = $encryptor->decrypt($encrypted);
```

### Encrypt User Data

```php
// Encrypt personal information before database storage
class User extends Model
{
public function setSocialSecurityNumber(string $ssn): void
{
$encryptor = new EncryptionService();
$this->ssn_encrypted = $encryptor->encrypt($ssn);
}

public function getSocialSecurityNumber(): ?string
{
if (!$this->ssn_encrypted) {
return null;
}

$encryptor = new EncryptionService();
return $encryptor->decrypt($this->ssn_encrypted);
}
}
```

## Best Practices

> **Success:** **Do's:** ✅ Generate a unique APP_KEY for each environment ✅ Store APP_KEY in .env file, not in code ✅ Rotate encryption keys periodically in production ✅ Use try-catch when decrypting untrusted data ✅ Encrypt sensitive data before storing in database or cookies

> **Danger:** **Don'ts:** ❌ Never commit APP_KEY to version control ❌ Don't share the same APP_KEY across multiple apps ❌ Don't use encryption for passwords (use hashing instead) ❌ Don't assume decryption will always succeed ❌ Don't encrypt data that doesn't need encryption (performance cost)

## Next Steps

- [Session & Cookie Encryption](/docs/session-cookies) - See encryption in action with sessions and cookies

- [Configuration](/docs/config) - Learn how to manage APP_KEY and other settings

- [DTO Validation](/docs/validation) - Validate data before encryption

## Learn More

This implementation is explained in detail with step-by-step tutorials, tests, and best practices at
[
**masterphp.eu** 
](https://masterphp.eu)

**Demo App:** See encryption in action with automatic cookie and session encryption. The demo application showcases XChaCha20-Poly1305 AEAD cipher with constraint-driven validation and PHPStan type narrowing.

[
View on Packagist
](https://packagist.org/packages/larafony/skeleton)
[
View on GitHub
](https://github.com/DJWeb-Damian-Jozwiak/larafony-demo-app)
