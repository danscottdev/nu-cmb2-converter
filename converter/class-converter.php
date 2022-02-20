<?php
/**
 * Handles actually converting each field, based on the field type.
 * Also handles interacting with the WPDB to make needed updates to wp_postmeta
 */

/** Converter */
class Converter {

	/**
	 * Instance of this class
	 *
	 * @var boolean
	 */
	public static $instance = false;

	/** Constructor */
	public function __construct() {
		// error_log( 'Converter launched' );
	}

	/** Singleton */
	public static function singleton() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Where the magic happens.
	 *
	 * @param string $post_type  Post type that the field is assigned to.
	 * @param string $meta_key   Name of the field we're converting.
	 * @param string $field_type Type of the field we're converting.
	 * @param string $arr_key    [OPTIONAL] if we're converting an image field that is nested within a group, then $meta_key = group id, $arr_key = img id.
	 * @return void
	 *
	 * @todo after refactors, I don't think we actually need $post_type here anymore.
	 */
	public function convert( $post_type, $meta_key, $field_type, $arr_key = '' ) {

		switch ( $field_type ) {
			case 'group':
				include_once plugin_dir_path( __FILE__ ) . '/class-field-type-group.php';
				new Field_Type_Group( $post_type, $meta_key );
				break;
			case 'checkbox':
				include_once plugin_dir_path( __FILE__ ) . '/class-field-type-checkbox.php';
				new Field_Type_Checkbox( $post_type, $meta_key );
				break;
			case 'image':
				include_once plugin_dir_path( __FILE__ ) . '/class-field-type-image.php';
				new Field_Type_Image( $post_type, $meta_key, $arr_key );
				break;
			case 'SPECIAL':
				// Special hardcoded case for affiliate CPT 'highlights' field.
				include_once plugin_dir_path( __FILE__ ) . '/class-unique-cases.php';
				new Unique_Case( $post_type, $meta_key );
				break;
			default:
				error_log( 'error: invalid field type: ' . $field_type );
		}
	}


	// Utility functions used across all field type converters.
	// @todo: Can probably split these out into their own Utilities{} file

	/**
	 * Update Row
	 * Updates existing row in wpdb via update_post_meta().
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  New Meta value - string or array depending on field type.
	 * @param string $field_type  FM/CMB2 field type. Only used for error logging.
	 */
	public function update_row( $post_id, $meta_key, $meta_value, $field_type ) {

		$updated_row = update_post_meta( $post_id, $meta_key, $meta_value );

		if ( false === $updated_row ) {
			error_log( 'ERROR Updating Row -- field type ' . $field_type . ' Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
			error_log( $meta_value );
		} else {
			error_log( 'Updated ' . $field_type . ' -- Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
		}

	}

	/**
	 * Insert Row
	 * Adds new row to wpdb via add_post_meta().
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  Meta value - string or array depending on field type.
	 * @param string $field_type  FM/CMB2 field type. Only used for error logging.
	 */
	public function insert_row( $post_id, $meta_key, $meta_value, $field_type ) {

		$inserted_row = add_post_meta( $post_id, $meta_key, $meta_value );

		if ( false === $inserted_row ) {
			error_log( 'ERROR Inserting Row -- field type ' . $field_type . ' Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
			error_log( $meta_value );
		} else {
			error_log( 'Added ' . $field_type . ' -- Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
		}

	}


	/**
	 * Delete Row
	 * Removes row from wpdb via delete_post_meta().
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $meta_key    Meta key.
	 * @param string $field_type  FM/CMB2 field type. Only used for error logging.
	 */
	public function delete_row( $post_id, $meta_key, $field_type ) {

		$deleted_row = delete_post_meta( $post_id, $meta_key );

		if ( false === $deleted_row ) {
			error_log( 'ERROR Deleting Row -- field type ' . $field_type . ' Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
		} else {
			error_log( 'Deleted ' . $field_type . ' -- Post ID: ' . $post_id . ' meta_key: ' . $meta_key );
		}

	}

	/**
	 * Generate WPDB SQL query
	 *
	 * @param string $meta_key -- meta_key we're querying for.
	 * */
	public function generate_sql( $meta_key ) {

		global $wpdb;
		$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"
					SELECT *
					FROM $wpdb->postmeta
					WHERE meta_key = %s
				",
				$meta_key
			)
		);

		return $posts;
	}
}
