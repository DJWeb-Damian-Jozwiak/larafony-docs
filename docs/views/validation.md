---
title: "DTO Validation"
description: "Type-safe validation using PHP 8.5 attributes and property hooks"
---

# DTO Validation

## What are DTOs?

Data Transfer Objects (DTOs) in Larafony are classes that validate and transform incoming request data.
They use PHP 8.5 features like attributes, property hooks, and asymmetric visibility for clean, type-safe validation.

## Creating a DTO

Extend `FormRequest` and add properties with validation attributes:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Attributes\IsValidated;
use Larafony\Framework\Validation\Attributes\MinLength;

class CreateNoteDto extends FormRequest
{
#[IsValidated]
#[MinLength(3)]
public protected(set) string $title;

#[IsValidated]
#[MinLength(10)]
public protected(set) string $content;
}
```

> **Info:** **Asymmetric Visibility:** `public protected(set)` means the property can be read publicly but only set within the class. This protects your data from external modification.

## Using DTOs in Controllers

Type-hint the DTO in your controller method. Validation happens automatically:

```php
use App\DTOs\CreateNoteDto;

#[Route('/notes', 'POST')]
public function store(CreateNoteDto $dto): ResponseInterface
{
// If we reach here, validation passed!

$note = new Note()->fill([
'title' => $dto->title,
'content' => $dto->content,
]);
$note->save();

return $this->redirect('/notes');
}
```

If validation fails, an exception is thrown automatically. You can catch and handle it in your error handler.

## Validation Attributes (13 Total)

Larafony provides 13 powerful validation attributes covering basic constraints, advanced conditional logic,
and custom validation with PHP 8.5 closures.

### Marker Attribute

#### #[IsValidated]

Marks a property for auto-population from request data. Required on all validated properties:

```php
#[IsValidated]
public protected(set) string $title;
```

### Basic Constraints

#### #[Required]

Field must not be null:

```php
#[IsValidated]
#[Required]
public protected(set) ?string $username;
```

#### #[Email]

Validates email format using `filter_var`:

```php
#[IsValidated]
#[Required]
#[Email]
public protected(set) ?string $email;
```

#### #[Min] / #[Max]

Numeric range validation:

```php
#[IsValidated]
#[Min(18)]
#[Max(120)]
public protected(set) ?int $age;
```

#### #[MinLength] / #[MaxLength] / #[Length]

String length validation:

```php
#[IsValidated]
#[MinLength(3)]
public protected(set) string $username;

#[IsValidated]
#[MaxLength(255)]
public protected(set) string $bio;

#[IsValidated]
#[Length(min: 8, max: 32)]
public protected(set) string $password;
```

#### #[StartsWith] / #[EndsWith]

String pattern matching:

```php
#[IsValidated]
#[StartsWith('https://')]
public protected(set) string $website;

#[IsValidated]
#[EndsWith('.com')]
public protected(set) string $domain;
```

### Advanced Conditional Validation (PHP 8.5 Closures)

#### #[RequiredWhen(Closure)]

Field required when closure returns true. Uses PHP 8.5 closures in attributes:

```php
#[IsValidated]
#[RequiredWhen(fn(array $data) => $data['type'] === 'business')]
public protected(set) ?string $companyName;

// Multiple conditions
#[IsValidated]
#[RequiredWhen(fn(array $data) =>
$data['country'] === 'US' && $data['state'] !== null
)]
public protected(set) ?string $zipCode;
```

#### #[RequiredUnless(Closure)]

Field required unless closure returns true (inverse of RequiredWhen):

```php
#[IsValidated]
#[RequiredUnless(fn(array $data) => !empty($data['phone']))]
public protected(set) ?string $alternativeContact;

// Email required unless social login is used
#[IsValidated]
#[RequiredUnless(fn(array $data) => !empty($data['social_provider']))]
public protected(set) ?string $email;
```

#### #[ValidWhen(Closure, message)]

Custom validation logic with closures. The closure receives the value and all data:

```php
#[IsValidated]
#[ValidWhen(
fn(mixed $value, array $data) => $value === $data['password'],
message: 'Passwords must match'
)]
public protected(set) string $password_confirmation;

// Age validation based on account type
#[IsValidated]
#[ValidWhen(
fn(mixed $value, array $data) =>
$data['type'] !== 'business' || ($value !== null && $value >= 18),
message: 'Must be 18+ for business accounts'
)]
public protected(set) ?int $age;
```

#### #[Confirmed]

Field confirmation matching. Looks for `{field}_confirmation`:

```php
#[IsValidated]
#[Required]
#[MinLength(8)]
#[Confirmed]
public protected(set) string $password;

#[IsValidated]
public protected(set) string $password_confirmation;
```

### PHP 8.5 First-Class Callables

Use first-class callable syntax (`self::method(...)`) for cleaner validation:

```php
class InvoiceRequest extends FormRequest
{
#[IsValidated]
#[Required]
public protected(set) string $invoiceType; // 'standard' or 'proforma'

// Using first-class callable syntax (PHP 8.5)
#[IsValidated]
#[RequiredWhen(self::isStandardInvoice(...))]
public protected(set) ?string $paymentMethod;

#[IsValidated]
#[ValidWhen(self::validInvoiceNumber(...), 'Invalid invoice format')]
public protected(set) ?string $invoiceNumber;

private static function isStandardInvoice(array $data): bool
{
return $data['invoiceType'] === 'standard';
}

private static function validInvoiceNumber(mixed $value, array $data): bool
{
if ($data['invoiceType'] === 'standard') {
return preg_match('/^INV-\d{4}-\d{4}$/', $value) === 1;
}

return preg_match('/^PRO-\d{4}-\d{4}$/', $value) === 1;
}
}
```

> **Success:** **PHP 8.5 Magic:** First-class callables (`self::method(...)`) provide clean, refactorable method references. The `(...)` syntax creates a closure from the method without verbose anonymous functions!

## Property Hooks for Transformation

Use property hooks to transform data automatically:

```php
<?php

namespace App\DTOs;

use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Attributes\IsValidated;

class CreateNoteDto extends FormRequest
{
#[IsValidated]
public protected(set) string $title;

#[IsValidated]
public protected(set) string $content;

// Transform comma-separated string to array
#[IsValidated]
public protected(set) string|array|null $tags {
get {
if (!isset($this->tags)) {
return null;
}
if (is_array($this->tags)) {
return $this->tags;
}

// Transform "php, framework, laravel" to ["php", "framework", "laravel"]
return array_map('trim', explode(',', $this->tags));
}
set => $this->tags = $value;
}
}
```

Now when you access `$dto->tags`, you always get an array, even if the input was a string!

## Optional Properties

Make properties optional by using nullable types:

```php
#[IsValidated]
public protected(set) ?string $description;

#[IsValidated]
public protected(set) string|null $notes;
```

## Complete Real-World Example

Here's a complete business registration DTO showcasing all validation features:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use Larafony\Framework\Validation\FormRequest;
use Larafony\Framework\Validation\Attributes\{
IsValidated,
Required,
Email,
MinLength,
MaxLength,
Length,
Min,
Max,
StartsWith,
RequiredWhen,
RequiredUnless,
ValidWhen,
Confirmed
};

class BusinessRegistrationDto extends FormRequest
{
// Basic validation
#[IsValidated]
#[Required]
public protected(set) string $type; // 'personal' or 'business'

// Email validation
#[IsValidated]
#[Required]
#[Email]
public protected(set) string $email;

// String length validation
#[IsValidated]
#[Required]
#[MinLength(3)]
#[MaxLength(50)]
public protected(set) string $name;

// Numeric range validation
#[IsValidated]
#[Required]
#[Min(18)]
#[Max(120)]
public protected(set) int $age;

// Conditional validation - business fields
#[IsValidated]
#[RequiredWhen(fn(array $data) => $data['type'] === 'business')]
#[MinLength(2)]
public protected(set) ?string $companyName;

#[IsValidated]
#[RequiredWhen(fn(array $data) => $data['type'] === 'business')]
#[Length(min: 9, max: 11)]
public protected(set) ?string $taxId;

// RequiredUnless - need email OR phone
#[IsValidated]
#[RequiredUnless(fn(array $data) => !empty($data['phone']))]
public protected(set) ?string $alternativeEmail;

// String pattern validation
#[IsValidated]
#[StartsWith('https://')]
public protected(set) ?string $website;

// Password with confirmation
#[IsValidated]
#[Required]
#[MinLength(8)]
#[Confirmed]
public protected(set) string $password;

#[IsValidated]
public protected(set) string $password_confirmation;

// Custom validation with closure
#[IsValidated]
#[ValidWhen(
fn(mixed $value, array $data) =>
$data['type'] !== 'business' || ($value !== null && $value >= 18),
message: 'Business accounts require age 18+'
)]
public protected(set) ?int $businessAge;

// Property hook for transformation
#[IsValidated]
public protected(set) string|array|null $interests {
get {
if (!isset($this->interests)) {
return null;
}
if (is_array($this->interests)) {
return $this->interests;
}
return array_map('trim', explode(',', $this->interests));
}
set => $this->interests = $value;
}
}
```

> **Info:** **All 13 Attributes in Action:** This example demonstrates: `#[IsValidated]` - Marker for all properties `#[Required]`, `#[Email]` - Basic constraints `#[MinLength]`, `#[MaxLength]`, `#[Length]` - String length `#[Min]`, `#[Max]` - Numeric ranges `#[StartsWith]` - Pattern matching `#[RequiredWhen]`, `#[RequiredUnless]` - Conditional requirements `#[ValidWhen]` - Custom validation logic `#[Confirmed]` - Field confirmation Property hooks - Data transformation

## Using the DTO

```php
#[Route('/users', 'POST')]
public function store(CreateUserDto $dto): ResponseInterface
{
$user = new User()->fill([
'name' => $dto->name,
'email' => $dto->email,
'password' => password_hash($dto->password, PASSWORD_DEFAULT),
'bio' => $dto->bio,
]);
$user->save();

// Handle interests (array of strings)
if ($dto->interests) {
foreach ($dto->interests as $interest) {
// Process each interest
}
}

return $this->redirect('/users');
}
```

## Form Example

HTML form that works with the DTO:

```html
<form method="POST" action="/notes">

<label>Title</label>
<input type="text" name="title" required>

<label>Content</label>

<label>Tags (comma-separated)</label>
<input type="text" name="tags" placeholder="php, framework, tutorial">

<button type="submit">Create Note
```

> **Success:** **Tip:** DTO property names must match form field names exactly. Use `name="title"` for a DTO property called `$title`.

## Quick Reference: All 13 Validation Attributes

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Description</th>
<th>Example
</thead>
<tbody>
<tr>
<td>`#[IsValidated]`</td>
<td>Marker for auto-population (required)</td>
<td>`#[IsValidated]`
<tr>
<td>`#[Required]`</td>
<td>Field must not be null</td>
<td>`#[Required]`
<tr>
<td>`#[Email]`</td>
<td>Validates email format</td>
<td>`#[Email]`
<tr>
<td>`#[Min]`</td>
<td>Minimum numeric value</td>
<td>`#[Min(18)]`
<tr>
<td>`#[Max]`</td>
<td>Maximum numeric value</td>
<td>`#[Max(120)]`
<tr>
<td>`#[MinLength]`</td>
<td>Minimum string length</td>
<td>`#[MinLength(3)]`
<tr>
<td>`#[MaxLength]`</td>
<td>Maximum string length</td>
<td>`#[MaxLength(255)]`
<tr>
<td>`#[Length]`</td>
<td>String length range</td>
<td>`#[Length(8, 32)]`
<tr>
<td>`#[StartsWith]`</td>
<td>String must start with prefix</td>
<td>`#[StartsWith('https://')]`
<tr>
<td>`#[EndsWith]`</td>
<td>String must end with suffix</td>
<td>`#[EndsWith('.com')]`
<tr>
<td>`#[RequiredWhen]`</td>
<td>Required when closure returns true</td>
<td>`#[RequiredWhen(fn($d) => $d['type'] === 'business')]`
<tr>
<td>`#[RequiredUnless]`</td>
<td>Required unless closure returns true</td>
<td>`#[RequiredUnless(fn($d) => !empty($d['phone']))]`
<tr>
<td>`#[ValidWhen]`</td>
<td>Custom validation with closure</td>
<td>`#[ValidWhen(fn($v, $d) => $v === $d['password'], 'Must match')]`
<tr>
<td>`#[Confirmed]`</td>
<td>Field must match {field}_confirmation</td>
<td>`#[Confirmed]`

**PHP 8.5 Required:** Features like closures in attributes (`#[RequiredWhen(fn...)]`)
and first-class callables (`self::method(...)`) require PHP 8.5+. These cutting-edge features
make Larafony's validation system more powerful than production frameworks limited to PHP 8.1.

## Next Steps

- [Learn about using DTOs in controllers →](/docs/controllers)

- [Learn about saving validated data to models →](/docs/models)
