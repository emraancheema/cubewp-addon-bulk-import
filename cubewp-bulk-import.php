<?php
/**
 * Plugin Name: CubeWP Bulk Import
 * Plugin URI: https://cubewp.com/downloads/cubewp-addon-bulk-import/
 * Description: CubeWP Bulk Import Plugin, an extension for CubeWP Framework, simplifies content import in WordPress, supporting large-scale imports and working seamlessly with WP All Import.
 * Version: 1.0.0
 * Author: CubeWP
 * Author URI: https://cubewp.com
 * Text Domain: cubewp-bulk-import
 * Domain Path: /languages/
 * @package cubewp-bulk-import
 */
defined( 'ABSPATH' ) || exit;

/* CUBEWP_BULK_IMPORT_PLUGIN_URL Defines current plugin version */
if ( ! defined( 'CUBEWP_BULK_IMPORT_VERSION' ) ) {
	define( 'CUBEWP_BULK_IMPORT_VERSION', '1.0.0' );
}

/* CUBEWP_BULK_IMPORT_PLUGIN_DIR Defines for load Php files */
if ( ! defined( 'CUBEWP_BULK_IMPORT_PLUGIN_DIR' ) ) {
	define( 'CUBEWP_BULK_IMPORT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

/* CUBEWP_BULK_IMPORT_PLUGIN_URL Defines for load JS and CSS files */
if ( ! defined( 'CUBEWP_BULK_IMPORT_PLUGIN_URL' ) ) {
	define( 'CUBEWP_BULK_IMPORT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * All CubeWP classes files to be loaded automatically.
 *
 * @param string $className Class name.
 */
spl_autoload_register( 'cubewp_bulk_import_autoload_classes' );
function cubewp_bulk_import_autoload_classes( $className ) {

	// If class does not start with our prefix (CubeWp), nothing will return.
	if ( false === strpos( $className, 'CubeWp' ) ) {
		return null;
	}
	// Replace _ with - to match the file name.
	$file_name = str_replace( '_', '-', strtolower( $className ) );

	// Calling class file.
	$files = array(
		CUBEWP_BULK_IMPORT_PLUGIN_DIR . 'cube/classes/class-' . $file_name . '.php'
	);

	// Checking if exists then include.
	foreach ( $files as $file ) {
		if ( file_exists( $file ) ) {
			require $file;
		}
	}

	return $className;
}

function cubewp_bulk_import() {
	if ( function_exists( 'CWP' ) ) {
		return CubeWp_Bulk_Import_Load::instance();
	}
}
add_action( 'plugins_loaded', 'cubewp_bulk_import' );
