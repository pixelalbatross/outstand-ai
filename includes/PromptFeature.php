<?php

namespace Outstand\WP\AI;

/**
 * Base class for the common "steerable prompt" feature shape: a global default
 * prompt (option) plus a per-post override (meta), injected into a single input
 * field of an AI plugin Ability. Resolution is strict: per-post > global > default.
 *
 * The actual injection happens client-side (an api-fetch middleware sets the
 * Ability input), since the AI plugin's editor does not send the post id to the
 * Ability and exposes no server-side input filter. This class owns the storage,
 * the settings section and the editor configuration; the JS reads the config.
 */
abstract class PromptFeature extends BaseModule {

	/**
	 * Unique feature id (kebab-case), e.g. `featured-image-prompt`.
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Human-readable feature label.
	 *
	 * @return string
	 */
	abstract public function get_label(): string;

	/**
	 * The Ability name whose input this feature steers, e.g. `ai/image-prompt-generation`.
	 *
	 * @return string
	 */
	abstract protected function get_ability(): string;

	/**
	 * The Ability input field to inject the resolved prompt into, e.g. `style`.
	 *
	 * @return string
	 */
	abstract protected function get_inject_field(): string;

	/**
	 * Help text shown under the settings field and the editor panel control.
	 *
	 * @return string
	 */
	abstract protected function get_description(): string;

	/**
	 * Whether a native site-wide source already provides this global default
	 * (e.g. a Gutenberg/AI Guidelines category) and currently has content. When
	 * true, this feature's own global option is not injected.
	 *
	 * @return bool
	 */
	protected function is_native_global_active(): bool {
		return false;
	}

	/**
	 * Whether a native site-wide guidelines feature is available (WordPress core
	 * or the Gutenberg plugin), regardless of whether it is filled in. When true,
	 * the settings field is deprecated in favor of it.
	 *
	 * @return bool
	 */
	protected function is_native_global_available(): bool {
		return false;
	}

	/**
	 * Example prompt shown as the field/panel placeholder to guide the user.
	 *
	 * @return string
	 */
	protected function get_example(): string {
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Run after post types are registered so the post-type list is complete.
		add_action( 'init', [ $this, 'register_meta' ], 20 );
	}

	/**
	 * Option key holding the global default prompt.
	 *
	 * @return string
	 */
	public function get_option_key(): string {
		return 'outstand_ai_' . str_replace( '-', '_', $this->get_id() );
	}

	/**
	 * Post meta key holding the per-post override prompt.
	 *
	 * @return string
	 */
	public function get_meta_key(): string {
		return '_outstand_ai_' . str_replace( '-', '_', $this->get_id() );
	}

	/**
	 * Register the per-post override meta on every supported post type.
	 *
	 * @return void
	 */
	public function register_meta(): void {

		foreach ( $this->get_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				$this->get_meta_key(),
				[
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => [ $this, 'can_edit_meta' ],
				]
			);
		}
	}

	/**
	 * Authorize editing the per-post override meta.
	 *
	 * @param  bool   $allowed   Whether editing is allowed.
	 * @param  string $meta_key  The meta key.
	 * @param  int    $object_id The post id.
	 * @return bool
	 */
	public function can_edit_meta( $allowed, $meta_key, $object_id ): bool {
		return current_user_can( 'edit_post', $object_id );
	}

	/**
	 * Register the feature's settings section and field on the given settings page.
	 *
	 * @param  string $page The settings page/group slug.
	 * @return void
	 */
	public function register_settings_section( string $page ): void {

		$section_id = $this->get_option_key() . '_section';

		register_setting(
			$page,
			$this->get_option_key(),
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			]
		);

		add_settings_section(
			$section_id,
			$this->get_label(),
			'__return_false',
			$page
		);

		add_settings_field(
			$this->get_option_key(),
			__( 'Default prompt', 'outstand-ai' ),
			[ $this, 'render_field' ],
			$page,
			$section_id
		);
	}

	/**
	 * Render the global default prompt textarea.
	 *
	 * @return void
	 */
	public function render_field(): void {

		$value     = (string) get_option( $this->get_option_key(), '' );
		$available = $this->is_native_global_available();

		// Deprecated in favor of the native Guidelines feature: lock the field when
		// it is empty so no new fallback content is added once Guidelines exists.
		$readonly = $available && '' === trim( $value );

		printf(
			'<textarea id="%1$s" name="%1$s" rows="6" class="large-text code"%3$s placeholder="%4$s">%2$s</textarea>',
			esc_attr( $this->get_option_key() ),
			esc_textarea( $value ),
			$readonly ? ' readonly' : '',
			$readonly ? '' : esc_attr( $this->get_example() )
		);

		if ( $available ) {
			printf(
				'<p class="description"><em>%s</em></p>',
				esc_html__( 'Deprecated — manage this prompt in Settings → Guidelines.', 'outstand-ai' )
			);
			return;
		}

		printf( '<p class="description">%s</p>', esc_html( $this->get_description() ) );
	}

	/**
	 * Configuration exposed to the editor JS (under `window.outstandAi.features`).
	 *
	 * @return array<string, mixed>
	 */
	public function get_editor_config(): array {
		return [
			'id'                 => $this->get_id(),
			'label'              => $this->get_label(),
			'description'        => $this->get_description(),
			'ability'            => $this->get_ability(),
			'injectField'        => $this->get_inject_field(),
			'metaKey'            => $this->get_meta_key(),
			'postTypes'          => array_values( $this->get_post_types() ),
			'global'             => (string) get_option( $this->get_option_key(), '' ),
			'example'            => $this->get_example(),
			'nativeGlobalActive' => $this->is_native_global_active(),
		];
	}

	/**
	 * Public post types that support featured images.
	 *
	 * @return string[]
	 */
	protected function get_post_types(): array {

		$post_types = get_post_types( [ 'public' => true ], 'names' );

		$supported = array_filter(
			$post_types,
			static function ( $post_type ) {
				return post_type_supports( $post_type, 'thumbnail' );
			}
		);

		return array_values( $supported );
	}
}
