# Outstand AI

> Add AI features to WordPress.

## Description

Outstand AI brings AI features to the WordPress editor, giving editors controls for AI behavior that WordPress and the [WordPress AI plugin](https://wordpress.org/plugins/ai/) don't expose on their own.

The first feature steers **featured image** generation: describe how the image should look, per post or as a site-wide default, and Outstand AI feeds that into the AI plugin's image prompt.

This is useful when:

- A post needs a specific featured image direction (style, mood, composition).
- You want a consistent default look for AI-generated images across the site.
- You want editors to control AI behavior from the editor, without touching code.

## How it works

1. Open a post in the Block Editor.
2. Open the **AI Prompts** sidebar (icon in the editor header, or the more menu).
3. Enter a prompt for the post (for example, the Featured Image direction). This overrides the site-wide default for that post.
4. Generate the featured image with the AI plugin as usual — Outstand AI injects your prompt.

Site-wide defaults are managed under **Settings → Outstand AI**. When the WordPress/Gutenberg **Guidelines** feature is available, manage the global direction there instead; Outstand AI defers to it and keeps the per-post override.

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

Features that extend the [WordPress AI plugin](https://wordpress.org/plugins/ai/) — such as Featured Image prompts — additionally require that plugin to be active with a configured AI connector. They stay hidden until it is.

## Extending

Register additional prompt-steering features with the `outstand_ai_features` filter:

```php
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
