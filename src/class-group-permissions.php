<?php
namespace Secdor;

class Group_Permissions {
	/**
	 * Allows developers to opt-out for section editing feature
	 */
	public static function get_supported_post_types( $output = 'objects' ) {

		$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		$supported_post_types = array();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'section-editing' ) ) {

				switch ( $output ) {

					case 'names':
						$supported_post_types[] = $post_type->name;
						break;

					case 'objects': default:
						$supported_post_types[] = $post_type;
						break;
				}
			}
		}

		return $supported_post_types;

	}
}
