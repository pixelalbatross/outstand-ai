<?php

namespace Outstand\WP\AI\Features;

use Outstand\WP\AI\PromptFeature;

/**
 * Steers the AI plugin's featured-image generation.
 *
 * The AI plugin builds the image prompt from the post via the
 * `ai/image-prompt-generation` Ability. This feature injects a resolved prompt
 * (per-post override, else global default) into that Ability's `style` input.
 */
class FeaturedImagePrompt extends PromptFeature {

	/**
	 * This feature steers the WordPress AI plugin's image-prompt Ability, so it
	 * only registers when that plugin is active.
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return defined( 'WPAI_VERSION' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return 'featured-image-prompt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Featured Image', 'outstand-ai' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_ability(): string {
		return 'ai/image-prompt-generation';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_inject_field(): string {
		return 'style';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_description(): string {
		return __( 'Describe how the featured image should look: subject, style, composition, lighting, mood, and colors. Keep it concise.', 'outstand-ai' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_example(): string {
		return __( 'e.g. Editorial photo of a tidy desk, soft natural light, muted tones, shallow depth of field, no text.', 'outstand-ai' );
	}

	/**
	 * The native global source is the AI/Gutenberg "Images" guideline: when it is
	 * set, the AI plugin already injects it into image generation, so this
	 * feature's own global option is only a fallback.
	 *
	 * @return bool
	 */
	protected function is_native_global_active(): bool {

		if ( ! function_exists( 'WordPress\AI\get_guidelines' ) ) {
			return false;
		}

		$guidelines = \WordPress\AI\get_guidelines( 'images' );
		$images     = is_array( $guidelines ) ? ( $guidelines['images'] ?? '' ) : '';

		return '' !== trim( (string) $images );
	}

	/**
	 * The native guidelines feature is available when the AI plugin reports its
	 * Guidelines service can read the guidelines post type (Gutenberg plugin or core).
	 *
	 * @return bool
	 */
	protected function is_native_global_available(): bool {

		if ( ! class_exists( '\WordPress\AI\Services\Guidelines' ) ) {
			return false;
		}

		return \WordPress\AI\Services\Guidelines::get_instance()->is_available();
	}
}
