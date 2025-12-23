---
title: "Welcome to Larafony Documentation"
description: "Modern PHP 8.5 framework built for clarity, not complexity."
---

# Welcome to Larafony Documentation

> **Info:** **Getting Started:** New to Larafony? Start with the [Project Structure](/docs/structure) guide to understand how everything is organized.

## What is Larafony?

Larafony is a production-ready PHP 8.5 framework that combines the developer experience of Laravel with the robustness of Symfony,
all while staying true to PSR standards. It's designed for developers who want:

- **Modern PHP** - Full PHP 8.5 support with attributes, property hooks, and asymmetric visibility

- **PSR Compliance** - Built on PSR-7, PSR-11, PSR-15, and PSR-3 standards

- **Zero Magic** - Clear, explicit code you can understand and modify

- **Attribute-Based** - Routes, relationships, validation—all defined with native PHP attributes

## Quick Start

Get up and running with Larafony in minutes:

```bash
# Create a new project
composer create-project larafony/skeleton my-app

# Navigate to your project
cd my-app

# Start the development server
php8.5 -S localhost:8000 -t public
```

Visit `http://localhost:8000` and you're ready to go!

## Core Features

### Active Record ORM

Eloquent-inspired ORM with attribute-based relationships. Define your models once and access relationships
through property hooks.

[Read Models & Relationships Guide →](/docs/models)

### Attribute Routing

Define routes directly on controller methods using PHP 8 attributes. No separate route files to maintain.

[Read Controllers & Routing Guide →](/docs/controllers)

### Type-Safe DTOs

Validate incoming requests with DTO classes that leverage PHP 8.5 property hooks and attributes for automatic validation.

[Read DTO Validation Guide →](/docs/validation)

### PSR-15 Middleware

Create middleware following PSR-15 standards. Attach them to routes using attributes for clean, declarative code.

[Read Middleware Guide →](/docs/middleware)

## Philosophy

> 
"The best framework is the one you can replace piece by piece - because you understand it completely."

Larafony is not just a framework—it's a learning tool. Every component is designed to be:

- **Understandable** - Clear code without hidden magic

- **Replaceable** - Swap any component for your preferred library

- **Educational** - Learn how modern frameworks work under the hood

## Getting Started

#### Project Structure

Learn how Larafony projects are organized and where to find everything.

[
Read Guide 
](/docs/structure)

#### Configuration & .env

Manage application configuration and environment variables.

[
Read Guide 
](/docs/config)

#### Container (PSR-11)

Dependency injection container with automatic autowiring.

[
Read Guide 
](/docs/container)

#### Controllers & Routing

Define routes with attributes and create RESTful controllers.

[
Read Guide 
](/docs/controllers)

## Database

#### Schema Builder & Migrations

Build database schemas with migrations using pipe operator.

[
Read Guide 
](/docs/schema-builder)

#### Query Builder

Build and execute SQL queries with fluent, type-safe API.

[
Read Guide 
](/docs/query-builder)

#### Models & Relationships

Active Record ORM with attribute-based relationships.

[
Read Guide 
](/docs/models)

## Views & Validation

#### Views & Blade

Build dynamic views with Blade templates and components.

[
Read Guide 
](/docs/views)

#### DTO Validation

Validate requests with type-safe DTOs and property hooks.

[
Read Guide 
](/docs/validation)

## HTTP & Logging

#### Middleware

Create PSR-15 compliant middleware for request/response processing.

[
Read Guide 
](/docs/middleware)

#### HTTP Client (PSR-18)

Make HTTP requests to external APIs with PSR-18 client.

[
Read Guide 
](/docs/http-client)

#### Logging (PSR-3)

Track application events with PSR-3 compliant logging.

[
Read Guide 
](/docs/logging)

## Security & Sessions

#### Sessions & Cookies

Encrypted session management with file and database drivers.

[
Read Guide 
](/docs/session-cookies)

#### Encryption

Modern libsodium XChaCha20-Poly1305 AEAD encryption.

[
Read Guide 
](/docs/encryption)

#### Authorization (RBAC)

Role and permission system with policy classes.

[
Read Guide 
](/docs/auth)

#### Mail

Native SMTP implementation with Mailable classes.

[
Read Guide 
](/docs/mail)

## Background Processing & Events

#### Queue & Jobs

Enterprise-grade job scheduling with ORM, Clock integration, and UUID support.

[
Read Guide 
](/docs/queue-jobs)

#### Events (PSR-14)

Event dispatcher with listener priority and stoppable propagation.

[
Read Guide 
](/docs/events)

#### Cache (PSR-6)

Multi-backend caching with Redis, Memcached, and file storage.

[
Read Guide 
](/docs/cache)

## Real-Time & AI

#### WebSockets

Real-time bidirectional communication with PHP 8.5 Fibers or ReactPHP.

[
Read Guide 
](/docs/websockets)

## Developer Tools

#### Debug Toolbar

Query monitoring, N+1 detection, and performance metrics.

[
Read Guide 
](/docs/debugbar)

#### Error Handling

Beautiful debug views for web and interactive REPL debugging in console.

[
Read Guide 
](/docs/error-handling-web)

#### Console Debugging

Interactive REPL-like debugging in terminal with variable inspection.

[
Read Guide 
](/docs/console-debugging)

#### Inertia.js Support

Build modern SPAs with Vue.js using server-side routing.

[
Read Guide 
](/docs/inertia)

## Bridges

#### Bridge Packages

Integrate Carbon, Monolog, Guzzle, Twig, Smarty, and more with one command.

[
Read Guide 
](/docs/bridges)
