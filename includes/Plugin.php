<?php

namespace Outstand\WP\AI;

class Plugin {

	/**
	 * Singleton instance of the Plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return Plugin The singleton instance.
	 */
	public static function get_instance(): Plugin {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enable plugin functionality.
	 *
	 * @return void
	 */
	public function enable(): void {

		$features = array_values(
			array_filter(
				$this->get_features(),
				static function ( $feature ) {
					return $feature instanceof PromptFeature && $feature->can_register();
				}
			)
		);

		$modules = array_merge(
			[
				new Settings( $features ),
				new Assets( $features ),
				new Integrations\TheSeoFramework(),
			],
			$features
		);

		foreach ( $modules as $module ) {
			if ( $module instanceof BaseModule && $module->can_register() ) {
				$module->register();
			}
		}
	}

	/**
	 * Get the registered features.
	 *
	 * @return PromptFeature[]
	 */
	public function get_features(): array {

		/**
		 * Filters the registered Outstand AI features.
		 *
		 * @param PromptFeature[] $features The feature objects.
		 */
		return (array) apply_filters(
			'outstand_ai_features',
			[
				new Features\FeaturedImagePrompt(),
				new Features\ExcerptPrompt(),
			]
		);
	}
}
