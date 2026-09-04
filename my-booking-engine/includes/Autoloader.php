<?php
/**
 * Autoloader class for MyBookingEngine namespace.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 *
 * Implements PSR-4 style autoloading for plugin classes.
 */
class Autoloader {

	/**
	 * Registers the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Loads class file based on namespace and class name.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'MyBookingEngine\\';

		// Base directory for the namespace prefix.
		$base_dir = MB_ENGINE_PATH . 'includes/';

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append with .php
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
