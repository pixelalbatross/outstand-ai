# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

### Changed

- The README now documents the Excerpt prompt, the `{DEFAULT_PROMPT}` token, and The SEO Framework integration.

## [1.3.2] - 2026-08-28

### Fixed

- Prompt injection now also covers ability run requests sent as `GET` with the input serialized into the query string, not just `POST` bodies. This keeps steering working if the WordPress AI plugin ever annotates its excerpt or featured-image abilities as `readonly` (which flips the core ability client to `GET`).

### Changed

- Internal: added JavaScript unit tests for the api-fetch middleware, and updated the excerpt-steering documentation to match the WordPress AI plugin 1.3.0 ability input schema (separate `content` input, `context` treated as additional context).

## [1.3.1] - 2026-07-10

### Changed

- Internal: moved the JavaScript source from `assets/js/` to `src/` to match the house plugin layout. No change to the shipped build.
- Internal: added a PHPUnit test setup (`wp-env`, bootstrap, and initial unit tests) for the module and asset-manifest helpers.

## [1.3.0] - 2026-06-30

### Added

- The SEO Framework integration: the WordPress AI plugin's Meta Description experiment now stores its generated description in The SEO Framework's `_genesis_description` field and registers it for REST so the editor save persists. Active only when both the AI plugin and The SEO Framework are present.

## [1.2.0] - 2026-06-29

### Added

- Excerpt prompt steering: set a per-post or site-wide prompt for the WordPress AI plugin's excerpt generation, managed from the same AI Prompts sidebar and Settings → Outstand AI. Supports the `{DEFAULT_PROMPT}` token. While a prompt is set it replaces the default post context; with no prompt set, native behavior is preserved.

## [1.1.0] - 2026-06-23

### Added

- Per-post prompts can embed a `{DEFAULT_PROMPT}` token to merge the global default prompt with post-specific details instead of replacing it. Applies to all prompt features, including the Featured Image prompt.

## [1.0.0] - 2026-06-20

- Initial release.
