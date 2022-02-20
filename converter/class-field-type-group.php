<?php
/**
 * Convert 'group' field type from FM to CMB2
 * NOTE this will only work for group fields that are NOT repeatable. Need separate special case for repeaters.
 *
 * FM groups are an array of key=>value pairs. CMB groups are an array of arrays of key=>value pairs. (i.e. extra outer-array wrapper)
 * This is because CMB groups are setup to be repeatable by default, whereas FM has repeaters as a separate field type.
 *
 * For simple groups, we can just add an extra outer-array to the existing array.
 * For repeater groups, use a different function.
 *
 * FM
 * [                               [
 *  'meta_key' => group_name,        'meta_key' => group_name,
 *  'meta_value' => [                'meta_value' => [
 *     key => value,                   [
 *     key => value,                     key => value,
 *     etc...                            key => value,
 *   ]                                   etc...
 * ]                                   ],
 *                                     [(if repeatable, additional arrays)]
 *                                 ]
 */

/** Field_Type_Group */
class Field_Type_Group extends Converter {

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type we're looking at. Can be post/page or custom post type.
	 * @param string $meta_key  Field name we're converting.
	 */
	public function __construct( $post_type, $meta_key ) {
		error_log( '----------- CONVERTING' );
		error_log( 'FIELD TYPE: Group (non-repeating)' );
		error_log( 'POST TYPE: ' . $post_type );
		error_log( 'META KEY: ' . $meta_key );

		$this->convert_group( $meta_key );
	}

	/**
	 * Convert 'group' type from FM to CMB2.
	 *
	 * @param string $meta_key -- field id slug that we're converting.
	 */
	private function convert_group( $meta_key ) {

		$rows_to_update = $this->generate_sql( $meta_key );

		foreach ( $rows_to_update as $row ) {

			$current_post_id    = $row->post_id;
			$current_meta_value = maybe_unserialize( $row->meta_value );

			// Check if value is already an array of arrays. If so then conversion is already done and we don't need to convert.
			if ( is_array( $current_meta_value ) && isset( $current_meta_value[0] ) && is_array( $current_meta_value[0] ) ) {
				error_log( 'Group Already Converted -- Post ID: ' . $current_post_id . ' meta_key: ' . $meta_key );
				continue;
			}

			$new_meta_value = [ $current_meta_value ];

			$this->update_row( $current_post_id, $meta_key, $new_meta_value, 'group' );
		}
	}
}