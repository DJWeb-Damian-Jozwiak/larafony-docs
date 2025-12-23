---
title: "Flysystem Storage Bridge"
description: "Unified filesystem abstraction with Flysystem - local, S3, FTP, SFTP, and more."
---

# Flysystem Storage Bridge

## Installation

```bash
composer require larafony/storage-flysystem

# Optional adapters
composer require league/flysystem-aws-s3-v3 # Amazon S3
composer require league/flysystem-ftp # FTP
composer require league/flysystem-sftp-v3 # SFTP
```

## Configuration

```php
use Larafony\Storage\Flysystem\ServiceProviders\FlysystemServiceProvider;

$app->withServiceProviders([
FlysystemServiceProvider::class
]);
```

## Basic Usage

```php
use Larafony\Storage\Flysystem\FlysystemStorage;

// Local storage
$storage = FlysystemStorage::local('/var/www/storage');

// Write file
$storage->put('uploads/photo.jpg', $content);

// Read file
$content = $storage->get('uploads/photo.jpg');

// Check existence
if ($storage->exists('uploads/photo.jpg')) {
// File exists
}

// Delete file
$storage->delete('uploads/photo.jpg');
```

## Multi-Disk Configuration

```php
use Larafony\Storage\Flysystem\FlysystemFactory;

final class UploadController extends Controller
{
#[Route('/upload', methods: ['POST'])]
public function upload(FlysystemFactory $factory): ResponseInterface
{
// Get specific disk
$local = $factory->disk('local');
$s3 = $factory->disk('s3');

// Upload to local first
$local->put('temp/upload.jpg', $content);

// Then sync to S3
$s3->put('backups/upload.jpg', $local->get('temp/upload.jpg'));

return new ResponseFactory()->createJsonResponse(['uploaded' => true]);
}
}
```

## S3 Configuration

```php
// config/filesystems.php
return [
'default' => 'local',

'disks' => [
'local' => [
'driver' => 'local',
'root' => storage_path('app'),
],

's3' => [
'driver' => 's3',
'key' => env('AWS_ACCESS_KEY_ID'),
'secret' => env('AWS_SECRET_ACCESS_KEY'),
'region' => env('AWS_DEFAULT_REGION'),
'bucket' => env('AWS_BUCKET'),
],
],
];
```

## Features

- **Unified API** - Same methods for all storage backends

- **Multiple adapters** - Local, S3, FTP, SFTP, and more

- **Disk switching** - Easy multi-storage management

- **Stream support** - Handle large files efficiently

- **Visibility control** - Public/private file permissions
