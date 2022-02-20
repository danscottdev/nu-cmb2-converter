<?php
	/**
	 * Convert 'checkbox' field type from FM to CMB2
	 *
	 * FM saves checkbox values as 1 (true) or blank (false)
	 * CMB2 saves checkbox values as "on" (true) or row will not be present (false)
	 */

/** Field_Type_Checkbox */
class Field_Type_Checkbox extends Converter {

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type we're looking at. Can be post/page or custom.
	 * @param string $meta_key  Field name we're converting.
	 */
	public function __construct( $post_type, $meta_key ) {
		error_log( '----------- CONVERTING' );
		error_log( 'FIELD TYPE: Checkbox' );
		error_log( 'POST TYPE: ' . $post_type );
		error_log( 'META KEY: ' . $meta_key );

		$this->convert_checkbox( $meta_key );
	}

	/**
	 * Convert 'checkbox' field type from FM to CMB2
	 *
	 * @param string $meta_key - meta_key we're converting.
	 * @todo Currently only works for top-level fields, need to account for checkbox fields that live inside a group field.
	 **/
	private function convert_checkbox( $meta_key ) {

		$rows_to_update = $this->generate_sql( $meta_key );

		foreach ( $rows_to_update as $row ) {

			$current_meta_value = $row->meta_value;
			$current_post_id    = $row->post_id;

			// Check if value has already been converted to CMB2 format.
			if ( 'on' === $current_meta_value ) {
				error_log( 'Checkbox already converted -- postID: ' . $row->post_id . '; meta_key: ' . $meta_key );
				continue;
			}

			$new_meta_value = '1' === $current_meta_value ? 'on' : '';

			if ( empty( $new_meta_value ) ) {
				// CMB omits row from db if value is false.
				$this->delete_row( $current_post_id, $meta_key, 'checkbox' );
			} else {
				$this->update_row( $current_post_id, $meta_key, $new_meta_value, 'checkbox' );
			}
		}
	}

}