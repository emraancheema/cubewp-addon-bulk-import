<?php
/**
 * CubeWP All Import Setup initializer.
 *
 * @package cubewp-addon-bulk-import/cube/classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CubeWp_All_Import_Setup
 */
class CubeWp_All_Import_Setup {

	private object $rapid_addon;

	private array $voided_field_types = array( 'repeating_field', 'post', 'user', 'taxonomy' );

	private array $fields;

	public function __construct() {
		$rapid_addon = CUBEWP_BULK_IMPORT_PLUGIN_DIR . 'cube/include/rapid-addon.php';
		if ( file_exists( $rapid_addon ) ) {
			include $rapid_addon;
		}

		self::create_cubewp_custom_fields_groups();
	}

	private function create_cubewp_custom_fields_groups() {
		$groups = cubewp_all_import_get_custom_field_groups();
		if ( $groups && is_array( $groups ) ) {
			foreach ( $groups as $group_id ) {
				$post_types   = get_post_meta( $group_id, '_cwp_group_types', true );
				$post_types   = ! empty( $post_types ) ? explode( ',', $post_types ) : array();
				$group_fields = get_post_meta( $group_id, '_cwp_group_fields', true );
				$group_fields = ! empty( $group_fields ) ? explode( ',', $group_fields ) : array();
				if ( ! empty( $group_fields ) ) {
					$group_name = get_post_field( 'post_name', $group_id );

					$this->rapid_addon = new RapidAddon( get_the_title( $group_id ), $group_name );

					$this->add_cubewp_group_fields( $group_fields );

					$this->rapid_addon->set_import_function( array( $this, 'cubewp_save_custom_fields' ) );

					$this->rapid_addon->run( array(
						'post_types' => $post_types
					) );
				}
			}
		}
	}

	private function add_cubewp_group_fields( array $group_fields ) {
		foreach ( $group_fields as $group_field ) {
			$field             = get_field_options( $group_field );
			$field_label       = $field['label'];
			$field_description = $field['description'];
			$field_type        = $field['type'];
			if ( in_array( $field_type, $this->voided_field_types ) ) {
				continue;
			}
			if ( $field_type == 'image' ) {
				$this->rapid_addon->add_field( $group_field, $field_label, 'image', null, $field_description );
			} else if ( $field_type == 'file' ) {
				$this->rapid_addon->add_field( $group_field, $field_label, 'file', null, $field_description );
			} else if ( $field_type == 'gallery' ) {
				$this->rapid_addon->import_images( $group_field, $field_label, 'images', function ( $post_id, $attachment_id, $image_filepath, $import_options ) use ( $group_field ) {
					$this->cubewp_save_gallery_custom_fields( $post_id, $attachment_id, $group_field, $image_filepath, $import_options );
				} );
			} else if ( $field_type == 'google_address' ) {
				$this->rapid_addon->add_options(
					null,
					$field_label,
					array(
						$this->rapid_addon->add_field( $group_field, esc_html__( 'Address', 'cubewp-bulk-import' ), 'text', null, $field_description ),
						$this->rapid_addon->add_field( $group_field . '_lat', esc_html__( 'Latitude', 'cubewp-bulk-import' ), 'text', null, $field_description ),
						$this->rapid_addon->add_field( $group_field . '_lng', esc_html__( 'Longitude', 'cubewp-bulk-import' ), 'text', null, $field_description )
					)
				);
			} else {
				$this->rapid_addon->add_field( $group_field, $field_label, 'text', null, $field_description );
			}
			$this->fields[ $group_field ] = $field;
		}
	}

	public function cubewp_save_gallery_custom_fields( $post_id, $attachment_id, $field_name, $image_filepath, $import_options ) {
		$field_options = isset( $this->fields[ $field_name ] ) && ! empty( $this->fields[ $field_name ] ) ? $this->fields[ $field_name ] : array();
		if ( ! empty( $field_options ) ) {
			$value                  = get_post_meta( $post_id, $field_name, true );
			$file_save              = isset( $field_options['files_save'] ) && ! empty( $field_options['files_save'] ) ? $field_options['files_save'] : 'ids';
			$separator              = isset( $field_options['files_save_separator'] ) && ! empty( $field_options['files_save_separator'] ) ? $field_options['files_save_separator'] : 'array';
			$field_options['value'] = $value;
			$value                  = cwp_handle_data_format( $field_options );
			$value[]                = $attachment_id;
			$_value                 = array();
			foreach ( $value as $val ) {
				$attachment_id = cwp_get_attachment_id( $val );
				if ( $file_save == 'urls' ) {
					$_value[] = wp_get_attachment_url( $attachment_id );
				} else {
					$_value[] = $attachment_id;
				}
			}
			$value = $_value;
			if ( $separator != 'array' ) {
				$value = implode( $separator, $value );
			}

			$this->rapid_addon->log( sprintf( esc_html__( '- Importing %s%s%s custom field.', 'cubewp-bulk-import' ), '<strong>', $field_name, '</strong>' ) );
			update_post_meta( $post_id, $field_name, $value );
		}
	}

	public function cubewp_save_custom_fields( $post_id, $data, $import_options ) {
		foreach ( $data as $field_name => $field_value ) {
			if ( $this->rapid_addon->can_update_meta( $field_name, $import_options ) ) {
				$field_options = isset( $this->fields[ $field_name ] ) && ! empty( $this->fields[ $field_name ] ) ? $this->fields[ $field_name ] : array();
				if ( empty( $field_options ) ) {
					continue;
				}
				$this->rapid_addon->log( sprintf( esc_html__( '- Importing %s%s%s custom field.', 'cubewp-bulk-import' ), '<strong>', $field_name, '</strong>' ) );
				$field_type = $field_options['type'];
				if ( $field_type == 'file' || $field_type == 'image' ) {
					$file_save  = isset( $field_options['files_save'] ) && ! empty( $field_options['files_save'] ) ? $field_options['files_save'] : 'ids';
					$attachment = isset( $field_value['attachment_id'] ) ? $field_value['attachment_id'] : '';
					if ( empty( $attachment ) ) {
						continue;
					}
					if ( $file_save == 'urls' ) {
						$attachment = wp_get_attachment_url( $attachment );
					}
					update_post_meta( $post_id, $field_name, $attachment );
				} elseif ( $field_type == 'checkbox' || ( $field_type == 'dropdown' && isset( $field_options['multiple'] ) && $field_options['multiple'] ) ) {
					$separator = isset( $field_options['files_save_separator'] ) && ! empty( $field_options['files_save_separator'] ) ? $field_options['files_save_separator'] : 'array';
					if ( $separator == 'array' ) {
						$field_value = explode( ',', $field_value );
						$field_value = array_map( 'ltrim', $field_value );
						$field_value = array_map( 'rtrim', $field_value );
					} else {
						$field_options['value'] = $field_value;
						$field_value            = cwp_handle_data_format( $field_options );
						$field_value            = array_map( 'ltrim', $field_value );
						$field_value            = array_map( 'rtrim', $field_value );
						$field_value            = implode( $separator, $field_value );
					}

					update_post_meta( $post_id, $field_name, $field_value );
				} else {
					update_post_meta( $post_id, $field_name, $field_value );
				}
			}
		}
	}

	/**
	 *
	 * @return void
	 */
	public static function init() {
		$CubeClass = __CLASS__;
		new $CubeClass;
	}
}