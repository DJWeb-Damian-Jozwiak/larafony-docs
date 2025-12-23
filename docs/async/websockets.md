---
title: "WebSockets"
description: "Real-time bidirectional communication with native PHP 8.5 Fibers engine and optional ReactPHP bridge for production scale"
---

# WebSockets

> **Info:** **Modular Architecture:** Core implementation uses PHP 8.5 Fibers with zero dependencies. For high-scale production, drop in the `larafony/websocket-react` bridge package.

## Overview

Larafony's WebSocket system provides complete real-time communication capabilities:

- **RFC 6455 Compliant** - Full WebSocket protocol with proper framing and handshakes

- **Zero Dependencies** - Core uses only PHP 8.5 Fibers and ext-sockets

- **Swappable Engines** - FiberEngine (core) or ReactEngine (bridge)

- **Event-Based** - Simple `on('event', callback)` API

- **Broadcast Support** - Send to all or filtered connections

- **Service Provider** - Extend to register custom handlers

## Quick Start

### 1. Basic Server

```php
use Larafony\Framework\WebSockets\Engine\FiberEngine;
use Larafony\Framework\WebSockets\Server;

$server = new Server(new FiberEngine(), '0.0.0.0', 8080);

$server->on('open', function ($data, $connection) {
echo "Connected: {$connection->getId()}\n";
});

$server->on('message', function ($payload, $connection) {
$connection->send("Echo: {$payload}");
});

$server->on('close', function ($data, $connection) {
echo "Disconnected: {$connection->getId()}\n";
});

$server->run();
```

### 2. Using Console Command

```bash
# Start with default config
php bin/larafony websocket:start

# Custom host and port
php bin/larafony websocket:start --host=127.0.0.1 --port=9000
```

### 3. Configuration

```php
// config/websocket.php
return [
'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
'port' => (int) env('WEBSOCKET_PORT', 8080),
];
```

## Architecture

> **Success:** **Clean Separation:** Protocol logic (Frame, Encoder, Decoder) is shared between engines. Only the I/O layer differs.

```text
┌─────────────────────────────────────────────────────┐
│ SHARED PROTOCOL LAYER │
├─────────────────────────────────────────────────────┤
│ Frame, Encoder, Decoder, Opcode, Handshake │
│ Server, EventDispatcher, Connection logic │
├─────────────────────────────────────────────────────┤
│ ENGINE ABSTRACTION (EngineContract) │
├──────────────────────┬──────────────────────────────┤
│ FiberEngine │ ReactEngine │
│ (Core - no deps) │ (Bridge - react/*) │
└──────────────────────┴──────────────────────────────┘
```

## Custom Event Handling

The server automatically parses JSON messages with `event` field:

```javascript
// Client sends:
ws.send(JSON.stringify({
event: 'chat_message',
data: { message: 'Hello!' }
}));
```

```php
// Server handles:
$server->on('chat_message', function ($data, $connection) {
$message = $data['message'];
// Process chat message...
});
```

## Broadcasting

```php
// Broadcast to all connections
$server->broadcast('Hello everyone!');

// Broadcast with filter (exclude sender)
$server->broadcast(
json_encode(['type' => 'notification', 'text' => 'New message']),
fn($conn) => $conn->getId() !== $currentConnection->getId()
);
```

## Service Provider Pattern

Extend `WebSocketServiceProvider` to register your handlers:

```php
namespace App\Providers;

use Larafony\Framework\WebSockets\Contracts\ServerContract;
use Larafony\Framework\WebSockets\ServiceProviders\WebSocketServiceProvider;

class ChatWebSocketProvider extends WebSocketServiceProvider
{
protected function registerDefaultHandlers(ServerContract $server): void
{
$server->on('chat_message', function ($data, $connection) {
// Handle chat messages
});

$server->on('user_typing', function ($data, $connection) use ($server) {
// Broadcast typing indicator
$server->broadcast(
json_encode(['event' => 'typing', 'user' => $data['userId']]),
fn($c) => $c->getId() !== $connection->getId()
);
});
}
}
```

## Bridge Package: ReactPHP

For production environments requiring high concurrency:

```bash
composer require larafony/websocket-react
```

```php
// Use ReactWebSocketServiceProvider instead of WebSocketServiceProvider
use Larafony\WebSocket\ReactWebSocketServiceProvider;

class MyReactProvider extends ReactWebSocketServiceProvider
{
protected function registerDefaultHandlers(ServerContract $server): void
{
// Same API as FiberEngine!
$server->on('message', fn($data, $conn) => ...);
}
}
```

> **Warning:** **Performance:** FiberEngine handles ~1000 concurrent connections. ReactEngine scales to 10,000+ with libuv/libev event loops.

## Protocol Components

### Frame

```php
use Larafony\Framework\WebSockets\Protocol\Frame;

// Create frames with factory methods
$textFrame = Frame::text('Hello');
$binaryFrame = Frame::binary($data);
$pingFrame = Frame::ping();
$closeFrame = Frame::close(1000, 'Normal closure');

// Send directly
$connection->send($textFrame);
$connection->send('Or just a string');
```

### Opcode Enum

```php
use Larafony\Framework\WebSockets\Protocol\Opcode;

Opcode::TEXT; // 1
Opcode::BINARY; // 2
Opcode::CLOSE; // 8
Opcode::PING; // 9
Opcode::PONG; // 10

$opcode->isControl(); // true for CLOSE, PING, PONG
```

## Client-Side (Vue.js Example)

```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
console.log('Connected');
};

ws.onmessage = (event) => {
const data = JSON.parse(event.data);
if (data.event === 'ai_response') {
displayMessage(data.data);
}
};

const sendMessage = (message) => {
ws.send(JSON.stringify({
event: 'chat_message',
data: { message }
}));
};
```

## Practical Example: AI Chat in demo-app 🤖

Complete demonstration of WebSocket integration with AI - from Vue frontend through WebSocket to backend calling OpenAI API.

### Controller (Inertia)

`demo-app/src/Controllers/ChatAIController.php`

```php
class ChatAIController extends Controller
{
public function index(ConfigContract $config): \Inertia\Response
{
return inertia('Chat/Index', [
'wsHost' => $config->get('websocket.host', 'localhost'),
'wsPort' => $config->get('websocket.port', 8080),
]);
}
}
```

### Message Listener with OpenAI

`demo-app/src/Listeners/ChatMessageListener.php`

```php
class ChatMessageListener
{
public function __construct(
private readonly ConfigContract $config,
) {}

public function __invoke(array $data, ConnectionContract $connection): void
{
$message = $data['message'] ?? '';
if (empty($message)) return;

$response = $this->callOpenAI($message);

$connection->send(Frame::text(json_encode([
'event' => 'ai_response',
'data' => ['message' => $response, 'timestamp' => time()],
])));
}

private function callOpenAI(string $message): string
{
$client = new CurlHttpClient();
$apiKey = $this->config->get('openai.api_key');

$request = new Request(
'POST',
new Uri('https://api.openai.com/v1/chat/completions'),
['Content-Type' => 'application/json', 'Authorization' => "Bearer {$apiKey}"],
json_encode([
'model' => $this->config->get('openai.model', 'gpt-4'),
'messages' => [['role' => 'user', 'content' => $message]],
])
);

$response = $client->sendRequest($request);
$body = json_decode((string) $response->getBody(), true);

return $body['choices'][0]['message']['content'] ?? 'Error';
}
}
```

### ServiceProvider

`demo-app/src/Providers/ChatWebSocketProvider.php`

```php
class ChatWebSocketProvider extends WebSocketServiceProvider
{
protected function registerDefaultHandlers(ServerContract $server): void
{
$listener = $this->container->get(ChatMessageListener::class);
$server->on('chat_message', $listener);

$server->on('open', fn($data, $conn) =>
$conn->send(json_encode(['event' => 'welcome', 'data' => 'Connected']))
);
}
}
```

### Vue Component

`demo-app/resources/js/Pages/Chat/Index.vue`

```javascript
const ws = new WebSocket(`ws://${props.wsHost}:${props.wsPort}`);

ws.onmessage = (event) => {
const data = JSON.parse(event.data);
if (data.event === 'ai_response') {
messages.value.push({
type: 'ai',
text: data.data.message,
timestamp: data.data.timestamp,
});
}
};

function sendMessage() {
ws.send(JSON.stringify({
event: 'chat_message',
data: { message: newMessage.value },
}));
}
```

### Data Flow

- User types message in Vue component

- Vue sends JSON via WebSocket: `{"event": "chat_message", "data": {"message": "..."}}`

- Server dispatches `chat_message` event to ChatMessageListener

- Listener calls OpenAI API via PSR-18 CurlHttpClient

- AI response sent back via WebSocket

- Vue receives and displays response in real-time

The entire flow works **without page reload**, with instant response thanks to persistent WebSocket connection.

## Framework Comparison 🔥

How does Larafony's WebSocket implementation compare to other PHP frameworks?

### vs Laravel Reverb

Laravel introduced Reverb in 2024 as its first-party WebSocket solution:

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Aspect</th>
<th>Laravel Reverb</th>
<th>Larafony
</thead>
<tbody>
<tr>
<td>Dependencies</td>
<td>Ratchet, Redis (for scaling)</td>
<td>**Zero** (core), ReactPHP (optional)
<tr>
<td>Protocol</td>
<td>Pusher protocol</td>
<td>**Native RFC 6455**
<tr>
<td>Architecture</td>
<td>Separate Reverb server process</td>
<td>**Integrated into framework**
<tr>
<td>Scaling</td>
<td>Requires Redis pub/sub</td>
<td>**Built-in broadcast**
<tr>
<td>Learning Curve</td>
<td>Pusher concepts, channels, events</td>
<td>**Simple `on('event', callback)`**
<tr>
<td>External Services</td>
<td>Often paired with Pusher/Soketi</td>
<td>**Fully self-contained**

> **Success:** **Larafony advantage:** No external services, no Pusher protocol abstraction, no Redis requirement. Just pure WebSockets with a clean, minimal API.

### vs Symfony

Symfony does not include a built-in WebSocket solution:

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Aspect</th>
<th>Symfony</th>
<th>Larafony
</thead>
<tbody>
<tr>
<td>Native Support</td>
<td>❌ None</td>
<td>**✅ Full RFC 6455**
<tr>
<td>Recommended Solution</td>
<td>Mercure (SSE) or third-party Ratchet</td>
<td>**Native FiberEngine or ReactEngine**
<tr>
<td>Protocol</td>
<td>Mercure uses Server-Sent Events</td>
<td>**True bidirectional WebSockets**
<tr>
<td>Integration</td>
<td>Manual setup required</td>
<td>**ServiceProvider + console command**
<tr>
<td>Real-time</td>
<td>One-way (SSE) or external package</td>
<td>**True bidirectional**

> **Success:** **Larafony advantage:** First-class WebSocket support built from scratch, not delegated to external projects or limited to Server-Sent Events.

### Why Larafony WebSockets Stand Out

<h5 class="card-title">Zero Dependencies</h5>

Core uses only PHP 8.5 Fibers and ext-sockets. No composer packages for basic functionality.

<h5 class="card-title">RFC 6455 From Scratch</h5>

Complete protocol implementation you can learn from and extend.

<h5 class="card-title">Swappable Engines</h5>

FiberEngine for dev, ReactEngine for production - same API, same handlers.

<h5 class="card-title">Simple Mental Model</h5>

No channels, no presence, no Pusher protocol. Just connections, events, broadcasts.

```php
// That's it. No Redis, no Pusher, no external services.
$server = new Server(new FiberEngine(), '0.0.0.0', 8080);
$server->on('message', fn($data, $conn) => $conn->send("Echo: $data"));
$server->run();
```

## Summary

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Feature</th>
<th>FiberEngine (Core)</th>
<th>ReactEngine (Bridge)
</thead>
<tbody>
<tr>
<td>Dependencies</td>
<td>None (ext-sockets)</td>
<td>react/event-loop, react/socket
<tr>
<td>Concurrency Model</td>
<td>PHP 8.5 Fibers</td>
<td>Event Loop + Callbacks
<tr>
<td>Scale</td>
<td>~1,000 connections</td>
<td>~10,000+ connections
<tr>
<td>Best For</td>
<td>Development, learning, simple apps</td>
<td>Production, high-traffic
