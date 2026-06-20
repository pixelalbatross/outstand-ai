<?php

namespace Outstand\WP\AI;

class Assets extends BaseModule {
	use GetAssetInfo;

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const HANDLE = 'outstand-ai-editor';

	/**
	 * Registered features.
	 *
	 * @var PromptFeature[]
	 */
	private array $features;

	/**
	 * Constructor.
	 *
	 * @param PromptFeature[] $features Registered features.
	 */
	public function __construct( array $features ) {
		$this->features = $features;
	}

	/**
	 * {@inheritDoc}
	 */
	public function can_register(): bool {
		return ! empty( $this->features );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->setup_asset_vars(
			dist_path: OUTSTAND_AI_DIST_PATH,
			fallback_version: OUTSTAND_AI_VERSION
		);

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );
	}

	/**
	 * Enqueue the editor bundle and localize each feature's configuration.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_scripts(): void {

		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$features = $this->get_editor_features( $screen->post_type ?? '' );

		if ( empty( $features ) ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			OUTSTAND_AI_DIST_URL . 'js/editor.js',
			$this->get_asset_info( 'editor', 'dependencies' ),
			$this->get_asset_info( 'editor', 'version' ),
			true
		);

		wp_add_inline_script(
			self::HANDLE,
			'window.outstandAi = ' . wp_json_encode( [ 'features' => $features ] ) . ';',
			'before'
		);

		wp_set_script_translations(
			self::HANDLE,
			'outstand-ai',
			OUTSTAND_AI_PATH . 'languages'
		);
	}

	/**
	 * Build the editor configuration for the features that apply to a post type.
	 *
	 * @param  string $post_type The current post type.
	 * @return array<string, array<string, mixed>>
	 */
	private function get_editor_features( string $post_type ): array {

		$features = [];

		foreach ( $this->features as $feature ) {
			$config = $feature->get_editor_config();

			if ( ! in_array( $post_type, (array) $config['postTypes'], true ) ) {
				continue;
			}

			$features[ $feature->get_id() ] = $config;
		}

		return $features;
	}
}
