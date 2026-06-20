<?php

namespace Outstand\WP\AI;

class Settings extends BaseModule {

	/**
	 * Settings page slug (also the settings group).
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'outstand-ai';

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
		return is_admin() && ! empty( $this->features );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'plugin_action_links_' . OUTSTAND_AI_BASENAME, [ $this, 'add_action_links' ] );
	}

	/**
	 * Add a "Settings" link to the plugin row actions.
	 *
	 * @param  string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_action_links( $links ): array {

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'outstand-ai' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add the Outstand AI settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {

		add_options_page(
			__( 'Outstand AI', 'outstand-ai' ),
			__( 'Outstand AI', 'outstand-ai' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Let each feature register its settings section and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {

		foreach ( $this->features as $feature ) {
			$feature->register_settings_section( self::PAGE_SLUG );
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE_SLUG );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
