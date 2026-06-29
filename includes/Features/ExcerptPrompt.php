<?php

namespace Outstand\WP\AI\Features;

use Outstand\WP\AI\PromptFeature;

/**
 * Steers the AI plugin's excerpt generation.
 *
 * The AI plugin generates excerpts via the `ai/excerpt-generation` Ability,
 * whose `context` input is normally the post ID (the server then pulls title and
 * terms from it). This feature injects a resolved prompt (per-post override, else
 * global default) into that `context` input. When a prompt is active it replaces
 * the post ID, so the excerpt is generated from the post body plus the steering
 * prompt; the server-fetched title/terms context is intentionally dropped while
 * steering. With no prompt set, the input is left untouched and native behavior
 * (post-ID context) is preserved.
 */
class ExcerptPrompt extends PromptFeature {

	/**
	 * This feature steers the WordPress AI plugin's excerpt Ability, so it only
	 * registers when that plugin is active.
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
		return 'excerpt-prompt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label(): string {
		return __( 'Excerpt', 'outstand-ai' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_ability(): string {
		return 'ai/excerpt-generation';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_inject_field(): string {
		return 'context';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type_support(): string {
		return 'excerpt';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_description(): string {
		return __( 'Steer the generated excerpt: tone, length, audience, and what to emphasize. Replaces the default post context while set.', 'outstand-ai' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_example(): string {
		return __( 'e.g. One punchy sentence, active voice, aimed at first-time readers; lead with the benefit.', 'outstand-ai' );
	}

	/**
	 * The native global source is the AI/Gutenberg "Copy" guideline: when it is
	 * set, the AI plugin already feeds it into excerpt generation, so this
	 * feature's own global option is only a fallback.
	 *
	 * @return bool
	 */
	protected function is_native_global_active(): bool {

		if ( ! function_exists( 'WordPress\AI\get_guidelines' ) ) {
			return false;
		}

		$guidelines = \WordPress\AI\get_guidelines( 'copy' );
		$copy       = is_array( $guidelines ) ? ( $guidelines['copy'] ?? '' ) : '';

		return '' !== trim( (string) $copy );
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
