<?php
/**
 * Application entry point.
 *
 * @package EPM
 */

namespace EPM;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates registered plugins and themes.
 */
final class App {

	/**
	 * The single instance of this class.
	 *
	 * @var App|null
	 */
	private static ?App $instance = null;

	/**
	 * Registered plugin paths.
	 *
	 * @var array<int, string>
	 */
	private array $plugins = array();

	/**
	 * Registered theme paths.
	 *
	 * @var array<int, string>
	 */
	private array $themes = array();

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return App
	 */
	public static function get_instance(): App {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Register a plugin directory.
	 *
	 * @param string $path Path to the plugin directory.
	 *
	 * @return void
	 */
	public function registerPlugin( string $path ): void {
		$this->plugins[] = $path;
	}

	/**
	 * Register a theme directory.
	 *
	 * @param string $path Path to the theme directory.
	 *
	 * @return void
	 */
	public function registerTheme( string $path ): void {
		$this->themes[] = $path;
	}

	/**
	 * Run the application.
	 *
	 * For Ephpicman themes, use index.php as the sole theme entry point.
	 * Other themes retain WordPress's default template hierarchy.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! defined( 'EPHPICMAN_THEME_FILE' ) ) {
			return;
		}

		$index_file = dirname( EPHPICMAN_THEME_FILE ) . '/index.php';

		if ( ! is_file( $index_file ) || ! is_readable( $index_file ) ) {
			return;
		}

		add_action(
			'template_redirect',
			static function () use ( $index_file ): void {
				// Preserve WordPress's special-purpose template handling.
				if ( is_feed() || is_robots() || is_trackback() || is_embed() ) {
					return;
				}

				// Render the Ephpicman entry point without resolving template hierarchy.
				include $index_file;
				exit;
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Terminate the application request.
	 *
	 * @return void
	 */
	public function terminate(): void {
		// Final request handling will be implemented here.
	}
}
