# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

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
