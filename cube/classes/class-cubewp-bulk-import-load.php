<?php
/**
 * CubeWP Bulk Import initializer.
 *
 * @package cubewp-addon-bulk-import/cube/classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CubeWp_Bulk_Import_Load
 */
class CubeWp_Bulk_Import_Load {

	/**
	 * The single instance of the class.
	 *
	 * @var CubeWp_Bulk_Import_Load
	 */
	protected static $Load = null;

	/**
	 * CubeWp_Load Constructor.
	 */
	public function __construct() {
		self::includes();
		if ( CWP()->is_request( 'admin' ) ) {
			self::admin_includes();
		}
	}

	/**
	 * Include required files for admin and frontend.
	 * @since  1.0.0
	 */
	public function includes() {
		$files = array(
			'include/helper.php'
		);
		foreach ( $files as $file ) {
			$file = CUBEWP_BULK_IMPORT_PLUGIN_DIR . 'cube/' . $file;
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Include required admin files.
	 * @since  1.0.0
	 */
	public function admin_includes() {
		add_action( 'init', array( 'CubeWp_All_Import_Setup', 'init' ), - 1 );
	}

	public static function instance() {
		if ( is_null( self::$Load ) ) {
			self::$Load = new self();
		}

		return self::$Load;
	}
}