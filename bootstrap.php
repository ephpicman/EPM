<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EpmLoader', false ) ) {
    final class EpmLoader {

        /**
         * Registered EPM version candidates.
         *
         * @var array<string, array{version: string, path: string}>
         */
        private static array $candidates = array();

        /**
         * Registered product namespace mappings.
         *
         * @var array<string, string>
         */
        private static array $products = array();

        /**
         * Whether the finalization hook has been registered.
         */
        private static bool $hook_registered = false;

        /**
         * Whether the autoloader has been initialized.
         */
        private static bool $initialized = false;

        /**
         * Register an EPM version candidate and product namespace.
         *
         * @param string $version      EPM version.
         * @param string $namespace    Product namespace.
         * @param string $plugin_path Path to the product's root directory.
         *
         * @return void
         */
        public static function register(
            string $version,
            string $namespace,
            string $plugin_path
        ): void {
            if ( self::$initialized ) {
                return;
            }

            $namespace   = trim( $namespace, '\\' ) . '\\';
            $plugin_path = realpath( $plugin_path );

            if ( false === $plugin_path ) {
                return;
            }

            $epm_path     = realpath( $plugin_path . '/epm' );
            $product_path = realpath( $plugin_path . '/src' );

            if (
                false === $epm_path
                || false === $product_path
                || ! is_dir( $epm_path . '/src' )
                || ! is_dir( $product_path )
                || ! preg_match(
                    '/^\\d+\\.\\d+\\.\\d+(?:-[0-9A-Za-z.-]+)?(?:\\+[0-9A-Za-z.-]+)?$/',
                    $version
                )
                || '' === $namespace
            ) {
                return;
            }

            self::$candidates[ $epm_path ] = array(
                'version' => $version,
                'path'    => $epm_path,
            );

            self::$products[ $namespace ] = $product_path;

            if ( self::$hook_registered ) {
                return;
            }

            self::$hook_registered = true;

            add_action(
                'after_setup_theme',
                array( self::class, 'initialize' ),
                PHP_INT_MAX
            );
        }

        /**
         * Initialize the autoloader using the highest registered EPM version.
         *
         * @return void
         */
        public static function initialize(): void {
            if ( self::$initialized || empty( self::$candidates ) ) {
                return;
            }

            $candidates = array_values( self::$candidates );

            usort(
                $candidates,
                static function ( array $a, array $b ): int {
                    return version_compare( $b['version'], $a['version'] );
                }
            );

            $selected = $candidates[0];
            $epm_src  = $selected['path'] . '/src';

            if ( ! is_dir( $epm_src ) ) {
                return;
            }

            self::$initialized = true;

            spl_autoload_register(
                static function ( string $class ) use ( $epm_src ): void {
                    self::load_class( $class, array(
                        'EPM\\' => $epm_src,
                    ) );
                }
            );

            foreach ( self::$products as $namespace => $src ) {
                spl_autoload_register(
                    static function ( string $class ) use ( $namespace, $src ): void {
                        self::load_class( $class, array(
                            $namespace => $src,
                        ) );
                    }
                );
            }
        }

        /**
         * Load a class using a namespace-to-directory mapping.
         *
         * @param string               $class    Fully qualified class name.
         * @param array<string,string> $prefixes Namespace prefixes and source directories.
         *
         * @return void
         */
        private static function load_class( string $class, array $prefixes ): void {
            foreach ( $prefixes as $prefix => $directory ) {
                if ( 0 !== strpos( $class, $prefix ) ) {
                    continue;
                }

                $relative_class = substr( $class, strlen( $prefix ) );
                $relative_path  = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );
                $file           = $directory . DIRECTORY_SEPARATOR . $relative_path . '.php';

                if ( is_file( $file ) && is_readable( $file ) ) {
                    require_once $file;
                }

                return;
            }
        }
    }
}

/*
 * Register the loader from the product's main plugin file, not here.
 *
 * The product must keep its directories named /epm and /src.
 * Pass only the product root path; the loader resolves both directories internally.
 *
 * Example (in the main plugin file):
 *
 * EpmLoader::register(
 *     '1.4.0',
 *     'Ephpicman\\MyPlugin\\',
 *     __DIR__
 * );
 */
