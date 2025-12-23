<?php

declare(strict_types=1);

namespace Larafony\Docs;

/**
 * Provider for accessing Larafony documentation files.
 *
 * This class provides static methods to access documentation
 * markdown files and metadata from the larafony/docs package.
 */
final class DocsProvider
{
    private static ?string $basePath = null;

    /** @var array<string, mixed>|null */
    private static ?array $meta = null;

    /**
     * Get the base path to the docs directory.
     */
    public static function basePath(): string
    {
        if (self::$basePath === null) {
            self::$basePath = dirname(__DIR__) . '/docs';
        }

        return self::$basePath;
    }

    /**
     * Get the full path to a documentation file.
     *
     * @param string $path Relative path within docs (e.g., 'http/controllers.md')
     */
    public static function getPath(string $path): string
    {
        return self::basePath() . '/' . ltrim($path, '/');
    }

    /**
     * Check if a documentation file exists.
     *
     * @param string $path Relative path within docs
     */
    public static function exists(string $path): bool
    {
        return file_exists(self::getPath($path));
    }

    /**
     * Read a documentation file's contents.
     *
     * @param string $path Relative path within docs
     * @return string|null File contents or null if not found
     */
    public static function read(string $path): ?string
    {
        $fullPath = self::getPath($path);

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        return $content !== false ? $content : null;
    }

    /**
     * Read a documentation file without frontmatter.
     *
     * @param string $path Relative path within docs
     * @return string|null Content without YAML frontmatter
     */
    public static function readContent(string $path): ?string
    {
        $content = self::read($path);

        if ($content === null) {
            return null;
        }

        // Remove YAML frontmatter
        return preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $content) ?? $content;
    }

    /**
     * Parse a documentation file and extract frontmatter + content.
     *
     * @param string $path Relative path within docs
     * @return array{frontmatter: array<string, string>, content: string}|null
     */
    public static function parse(string $path): ?array
    {
        $content = self::read($path);

        if ($content === null) {
            return null;
        }

        $frontmatter = [];

        // Extract YAML frontmatter
        if (preg_match('/^---\s*\n(.+?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $frontmatterRaw = $matches[1];
            $content = $matches[2];

            // Simple YAML parsing for key: "value" pairs
            foreach (explode("\n", $frontmatterRaw) as $line) {
                if (preg_match('/^(\w+):\s*"(.+)"$/', trim($line), $kv)) {
                    $frontmatter[$kv[1]] = stripslashes($kv[2]);
                }
            }
        }

        return [
            'frontmatter' => $frontmatter,
            'content' => $content,
        ];
    }

    /**
     * Get documentation metadata (sections, pages, navigation).
     *
     * @return array<string, mixed>
     */
    public static function getMeta(): array
    {
        if (self::$meta === null) {
            $metaPath = self::basePath() . '/meta.json';

            if (file_exists($metaPath)) {
                $json = file_get_contents($metaPath);
                self::$meta = $json !== false ? (json_decode($json, true) ?? []) : [];
            } else {
                self::$meta = [];
            }
        }

        return self::$meta;
    }

    /**
     * Get all documentation sections.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSections(): array
    {
        return self::getMeta()['sections'] ?? [];
    }

    /**
     * Find a section by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findSection(string $sectionId): ?array
    {
        foreach (self::getSections() as $section) {
            if ($section['id'] === $sectionId) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Find a page within a section by slug.
     *
     * @return array{file: string, title: string, slug: string}|null
     */
    public static function findPage(string $sectionId, string $slug): ?array
    {
        $section = self::findSection($sectionId);

        if ($section === null) {
            return null;
        }

        foreach ($section['pages'] as $page) {
            if ($page['slug'] === $slug) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Get all available documentation paths.
     *
     * @return array<int, string> List of relative paths to all markdown files
     */
    public static function getAllPaths(): array
    {
        $paths = [];

        foreach (self::getSections() as $section) {
            foreach ($section['pages'] as $page) {
                $paths[] = $section['id'] . '/' . $page['file'];
            }
        }

        return $paths;
    }

    /**
     * Clear cached metadata (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$meta = null;
        self::$basePath = null;
    }
}
