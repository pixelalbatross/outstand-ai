<?php

namespace Outstand\WP\AI\Integrations;

use Outstand\WP\AI\BaseModule;

/**
 * Teaches the WordPress AI plugin's Meta Description experiment to store its
 * generated description in The SEO Framework's field.
 *
 * The experiment generates the description but persists it client-side through
 * the REST data layer, keyed by whatever meta key its `SEO_Integration` resolves
 * for the active SEO plugin. The SEO Framework is not in that built-in list, and
 * it does not expose its description meta to REST, so this module does two things:
 *
 * 1. Registers The SEO Framework with the experiment via the
 *    `wpai_meta_description_seo_plugins` filter, pointing it at `_genesis_description`.
 * 2. Registers `_genesis_description` with `show_in_rest` so the client-side save
 *    round-trips. The SEO Framework reads the value back via plain `get_post_meta`,
 *    so it renders the AI-written description regardless of who stored it.
 */
class TheSeoFramework extends BaseModule {

	/**
	 * Slug the experiment uses to key The SEO Framework in its supported list.
	 *
	 * @var string
	 */
	private const PLUGIN_SLUG = 'the-seo-framework';

	/**
	 * The SEO Framework plugin file, matched against active plugins.
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'autodescription/autodescription.php';

	/**
	 * Post meta key The SEO Framework stores the description in.
	 *
	 * @var string
	 */
	private const META_KEY = '_genesis_description';

	/**
	 * This module steers the WordPress AI plugin's Meta Description experiment, so
	 * it only registers when that plugin is active.
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return defined( 'WPAI_VERSION' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'wpai_meta_description_seo_plugins', [ $this, 'add_seo_plugin' ] );
		// Run after post types are registered so the post-type list is complete.
		add_action( 'init', [ $this, 'register_meta' ], 20 );
	}

	/**
	 * Register The SEO Framework in the experiment's supported SEO plugin list.
	 *
	 * @param  array<string, array{file: string, meta_key: string}> $plugins Supported plugins.
	 * @return array<string, array{file: string, meta_key: string}>
	 */
	public function add_seo_plugin( $plugins ): array {

		$plugins                      = is_array( $plugins ) ? $plugins : [];
		$plugins[ self::PLUGIN_SLUG ] = [
			'file'     => self::PLUGIN_FILE,
			'meta_key' => self::META_KEY,
		];

		return $plugins;
	}

	/**
	 * Expose `_genesis_description` to REST so the experiment's client-side save
	 * persists. Only registered when The SEO Framework is present, to avoid adding
	 * a stray REST meta on sites without it.
	 *
	 * @return void
	 */
	public function register_meta(): void {

		if ( ! defined( 'THE_SEO_FRAMEWORK_PRESENT' ) ) {
			return;
		}

		$post_types = get_post_types( [ 'show_in_rest' => true ], 'names' );

		foreach ( $post_types as $post_type ) {

			if ( 'attachment' === $post_type ) {
				continue;
			}

			register_post_meta(
				$post_type,
				self::META_KEY,
				[
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => [ $this, 'can_edit_meta' ],
				]
			);
		}
	}

	/**
	 * Authorize editing The SEO Framework description meta.
	 *
	 * @param  bool   $allowed   Whether editing is allowed.
	 * @param  string $meta_key  The meta key.
	 * @param  int    $object_id The post id.
	 * @return bool
	 */
	public function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		return current_user_can( 'edit_post', $object_id );
	}
}
