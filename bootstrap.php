<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

if (!class_exists('EphpicManLoader', false)) {
    final class EphpicManLoader
    {
        /** @var array<string, array{version: string, path: string}> */
        private static array $availableFrameworkInstances = [];
        /** @var array<string, string> */
        private static array $products = [];
        private static bool $isFinalizationHookRegistered = false;
        private static bool $initialized = false;

        public static function register(string $pluginPath, string $pluginNamespace, string $pluginVersion): void
        {
            if (self::$initialized)
                return;

            $pluginNamespace = trim($pluginNamespace, '\\');
            if ('' === $pluginNamespace)
                return;

            $pluginNamespace .= '\\';

            $pluginPath = realpath($pluginPath);
            if (false === $pluginPath)
                return;

            $frameworkPath = "{$pluginPath}/epm";
            $productPath = "{$pluginPath}/src";

            if (
                !is_dir("{$frameworkPath}/src") ||
                !is_dir($productPath) ||
                !preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $pluginVersion)
            ) {
                return;
            }

            self::$availableFrameworkInstances[$frameworkPath] = [
                'version' => $pluginVersion,
                'path' => $frameworkPath,
            ];

            self::$products[$pluginNamespace] = $productPath;

            if (self::$isFinalizationHookRegistered)
                return;

            self::$isFinalizationHookRegistered = true;

            add_action('after_setup_theme', [self::class, 'initialize'], PHP_INT_MAX);
        }

        public static function initialize(): void
        {
            if (self::$initialized || empty(self::$availableFrameworkInstances))
                return;

            $availableFrameworkInstances = array_values(self::$availableFrameworkInstances);

            usort(
                $availableFrameworkInstances,
                static fn(array $a, array $b): int => version_compare($b['version'], $a['version'])
            );

            $selected = $availableFrameworkInstances[0];
            $frameworkSourcePath = $selected['path'] . '/src';

            if (!is_dir($frameworkSourcePath))
                return;

            self::$initialized = true;

            spl_autoload_register(
                static function (string $class) use ($frameworkSourcePath): void {
                    self::autoloadClass($class, ['EphpicMan\\Framework\\' => $frameworkSourcePath]);
                }
            );

            foreach (self::$products as $pluginNamespace => $src) {
                spl_autoload_register(
                    static function (string $class) use ($pluginNamespace, $src): void {
                        self::autoloadClass($class, array(
                            $pluginNamespace => $src,
                        ));
                    }
                );
            }
        }

        private static function autoloadClass(string $class, array $prefixes): void
        {
            foreach ($prefixes as $prefix => $directory) {
                if (0 !== strpos($class, $prefix))
                    continue;

                $relativeClass = substr($class, strlen($prefix));
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
                $file = $directory . DIRECTORY_SEPARATOR . $relativePath . '.php';

                if (is_file($file) && is_readable($file))
                    require_once $file;

                return;
            }
        }
    }

    add_action('wp_loaded', static fn(): void => EphpicMan\Framework\App::getInstance()->run());
}

/*
 * Register the loader from the product's main plugin file, not here.
 *
 * The product must keep its directories named /epm and /src.
 * Pass only the product root path; the loader resolves both directories internally.
 *
 * Example (in the main plugin file):
 *
 * EphpicManLoader::register(
 *     __DIR__,
 *     'EphpicMan\\MyPlugin\\',
 *     '1.4.0',
 * );
 */
