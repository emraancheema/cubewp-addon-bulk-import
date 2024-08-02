<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'cubewp_all_import_get_custom_field_groups' ) ) {
	function cubewp_all_import_get_custom_field_groups() {
		$args = array(
			'post_type'      => 'cwp_form_fields',
			'posts_per_page' => - 1,
			'fields'         => 'ids'
		);

		return get_posts( $args );
	}
}