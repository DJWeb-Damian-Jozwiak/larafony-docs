# Larafony Documentation

Official documentation for [Larafony Framework](https://larafony.com) - Modern PHP 8.5 framework built for clarity, not complexity.

## Structure

```
larafony-docs/
├── getting-started/     # Introduction, structure, bootstrap
├── architecture/        # Container, config, auth, cache
├── http/               # Controllers, middleware, HTTP client
├── database/           # Schema builder, query builder, models
├── views/              # Blade templates, Inertia.js, validation
├── security/           # Encryption, sessions, cookies
├── communication/      # Mail
├── utilities/          # Logging, events
├── async/              # Queue, jobs, WebSockets
├── debugging/          # DebugBar, error handling
├── bridges/            # Third-party integrations
└── meta.json           # Navigation structure and metadata
```

## Usage

### As Source of Truth

This repository serves as the single source of truth for all Larafony documentation. It's consumed by:

- **[larafony.com](https://larafony.com)** - Official documentation website
- **[larafony-mcp](https://github.com/...)** - MCP server for AI assistants

### File Format

Each documentation file uses Markdown with YAML frontmatter:

```markdown
---
title: "Page Title"
description: "Brief description for SEO"
---

# Page Title

Content goes here...
```

### Navigation Structure

The `meta.json` file defines the documentation structure:

```json
{
  "sections": [
    {
      "id": "section-id",
      "title": "Section Title",
      "icon": "bootstrap-icon-name",
      "pages": [
        {"file": "page.md", "title": "Page Title", "slug": "url-slug"}
      ]
    }
  ]
}
```

## Contributing

1. Fork this repository
2. Edit or add Markdown files
3. Update `meta.json` if adding new pages
4. Submit a pull request

## License

MIT License - see [LICENSE](LICENSE)
