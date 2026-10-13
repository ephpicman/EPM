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
	 * @return void
	 */
	public function run(): void {
		// Application orchestration will be implemented here.
	}
}
