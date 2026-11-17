# Outstand AI

> Add AI features to WordPress.

## Description

Outstand AI brings AI features to the WordPress editor, with controls for AI behavior that WordPress and the [WordPress AI plugin](https://wordpress.org/plugins/ai/) don't expose on their own.

This is useful when:

- A post needs a specific direction for its featured image or excerpt.
- You want a consistent default for AI-generated content across the site.
- You want editors to control AI behavior from the editor, without touching code.

## Features

- **Featured Image prompt:** describe how the featured image should look (subject, style, composition, mood). Outstand AI feeds it into the AI plugin's image prompt.
- **Excerpt prompt:** steer the generated excerpt (tone, length, audience, emphasis). While a prompt is set, it replaces the default post context (title and terms) that the AI plugin sends. With no prompt set, the AI plugin works as usual.
- **The SEO Framework integration:** the AI plugin's Meta Description experiment saves its generated description to The SEO Framework's description field. Active only when both plugins are active.

Each prompt can be set per post or as a site-wide default.

## How it works

1. Open a post in the Block Editor.
2. Open the **AI Prompts** sidebar (icon in the editor header, or the more menu).
3. Enter a prompt for the post (for example, the Featured Image or Excerpt direction). This overrides the site-wide default for that post.
4. Generate the featured image or excerpt with the AI plugin as usual — Outstand AI injects your prompt.

Site-wide defaults are managed under **Settings → Outstand AI**. When the WordPress/Gutenberg **Guidelines** feature is available, manage the global direction there instead (the **Images** guideline for featured images, the **Copy** guideline for excerpts); Outstand AI defers to it and keeps the per-post override.

### Merge the default into a per-post prompt

Add the `{DEFAULT_PROMPT}` token to a per-post prompt to keep the site-wide default from **Settings → Outstand AI** and add post-specific details to it. Outstand AI replaces the token with the default. Without the token, the per-post prompt replaces the default.

```text
{DEFAULT_PROMPT}
Show a mountain landscape at sunrise.
```

## Installation

### Manual Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/pixelalbatross/outstand-ai/releases/latest).
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require outstand/ai
```

2. Run `composer install`.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Requirements

- WordPress 6.7+
- PHP 8.2+

Features that extend the [WordPress AI plugin](https://wordpress.org/plugins/ai/) — such as the Featured Image and Excerpt prompts — additionally require that plugin to be active with a configured AI connector. They stay hidden until it is.

The SEO Framework integration requires the WordPress AI plugin and [The SEO Framework](https://wordpress.org/plugins/autodescription/) to be active. It works with the AI plugin's Meta Description experiment.

## Extending

Register additional prompt-steering features with the `outstand_ai_features` filter. Each feature must extend `Outstand\WP\AI\PromptFeature`:

```php
use Outstand\WP\AI\PromptFeature;

class My_Custom_Prompt_Feature extends PromptFeature {
    // Implement get_id(), get_label(), get_ability(),
    // get_inject_field() and get_description().
    // Override get_post_type_support() when the feature needs a
    // post type support other than 'thumbnail' (for example, 'excerpt').
}

add_filter( 'outstand_ai_features', function ( $features ) {
    $features[] = new My_Custom_Prompt_Feature();
    return $features;
} );
```

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/pixelalbatross/outstand-ai/blob/main/CHANGELOG.md).

## Credits

The AI Prompts sidebar icon is from the [Industrial Sharp UI Icons](https://www.svgrepo.com/svg/486520/ai) collection by Siemens AG, licensed under the [MIT License](https://opensource.org/licenses/MIT).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
