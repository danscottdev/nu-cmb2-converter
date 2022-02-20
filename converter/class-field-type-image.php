<?php
/**
 * Convert images from FM to CMB2
 * FM saves images in db as simple ID's
 * CMB2 saves images as URLs, **AND** creates a separate '[meta_key]_id' row to save the ID
 *
 *  FM                              CMB
 * [                               [
 *   'meta_key'   => 'img',  ===>    'meta_key' => 'img',
 *   'meta_value' => '662'   ===>    'meta_value' => 'http://nusystem.org/wp-content/uploads/2021/09/NUS_SEAL.png'
 * ]                               ]
 *                                 [
 *                                   'meta_key' => 'img_id',
 *                                   'meta_value' => 662
 *                                 ]
 *
 * Because of this, our conversion from FM-to-CMB consists of 2 steps:
 * 1) Create a new meta row for [meta_key]_id, whose value is copied from the existing row
 * 2) Convert the existing meta row from its WP Attachment ID to the actual file path string.
 */

/** Field_Type_Image */
class Field_Type_Image extends Converter {

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type we're looking at. Can be post/page or custom.
	 * @param string $meta_key  Field name we're converting.
	 * @param string $arr_key   Only required if image field is nested within a group, then $meta_key = group id, $arr_key = img id. Otherwise defaults to empty string.
	 */
	public function __construct( $post_type, $meta_key, $arr_key ) {
		error_log( '----------- CONVERTING' );
		error_log( 'FIELD TYPE: Image' );
		error_log( 'POST TYPE: ' . $post_type );
		error_log( 'META KEY: ' . $meta_key );
		error_log( 'ARR KEY: ' . $arr_key );

		$this->convert_images( $meta_key, $arr_key );
	}

	/**
	 * Convert image from FM to CMB2 format
	 *
	 * @param string $meta_key Current meta key.
	 * @param string $arr_key  OPTIONAL if image field is nested within a group, then $meta_key = group id, $arr_key = img id.
	 * @return void
	 */
	private function convert_images( $meta_key, $arr_key ) {

		$posts = $this->generate_sql( $meta_key );

		foreach ( $posts as $row ) {
			$this->create_new_image_meta_row( $row, $arr_key );
			$this->update_existing_image_meta_row( $row, $arr_key );
		}
	}

	/**
	 * Create new meta entry for "[meta_key]_id"
	 * Takes the existing meta_value (which FM saves as an ID), and create a new meta_key_id row that has the same number
	 * It's important that this step gets called before we convert the original ID to a URL string
	 *
	 * @param array  $row     -> current db row we're working with.
	 * @param string $arr_key -> OPTIONAL but required if our image field is within a group, which means the meta_value is an array. $arr_key is where the image ID lives within that array.
	 * */
	private function create_new_image_meta_row( $row, $arr_key ) {

		$current_post_id    = $row->post_id;
		$meta_key           = $row->meta_key;
		$current_meta_value = get_post_meta( $current_post_id, $meta_key, true );

		if ( ! empty( $arr_key ) ) {

			if ( isset( $current_meta_value[ $arr_key ] ) && is_numeric( $current_meta_value[ $arr_key ] ) ) {

				/**
				 * If we're here, that means the image is nested within a broader group array.
				 * Since we need to preserve all the other data within the group: we need to take the current array,
				 * add a new key/value pair to that array, and then UPDATE the existing meta row.
				 */

				$image_id     = $current_meta_value[ $arr_key ];
				$new_meta_key = $arr_key . '_id';

				$new_meta_value                  = $current_meta_value;
				$new_meta_value[ $new_meta_key ] = $image_id;

				$this->update_row( $current_post_id, $meta_key, $new_meta_value, 'image_id [within group]' );

			} else {

				/**
				 * If we're here, that means the image field exists within a group, but isn't actually set to a value.
				 * With FieldManager, this happens if a group has 2 or more image fields, but not all of them are used/set on a given post.
				 */
				error_log( 'Skipping image *_id field -- No image ID found -- post ID: ' . $current_post_id . '; meta_key ' . $meta_key . '; arr_key: ' . $arr_key );
				return;

			}
		} else {

			/**
			 * If we're here, the image is just a standalone meta field (not within a group).
			 * Just need to create a new *_id row with the same value.
			 */

			$new_meta_key = $meta_key . '_id';

			// If the '*_id' row already exists for this post ID, abort.
			if ( get_post_meta( $current_post_id, $new_meta_key, true ) ) {
				error_log( 'Skipping Row -- *_id field already exists -- post ID: ' . $current_post_id . '; meta_key ' . $meta_key );
				return;
			}

			$this->insert_row( $current_post_id, $new_meta_key, $current_meta_value, 'image_id [standalone]' );
		}

	}

	/**
	 * Update existing image meta row
	 * Takes the existing meta_value (which FM saves as an ID), and coverts it to a URL for CMB2
	 *
	 * @param array  $row     -> current db row we're working with.
	 * @param string $arr_key -> OPTIONAL but required if our image field is within a group, which means the meta_value is an array. $arr_key is where the image ID lives within that array.
	 * @return void
	 */
	private function update_existing_image_meta_row( $row, $arr_key ) {

		$current_post_id = $row->post_id;
		$meta_key        = $row->meta_key;

		// Can be either array() or int.
		$current_meta_value = get_post_meta( $current_post_id, $meta_key, true );

		if ( ! empty( $arr_key ) ) {
			// If image is nested within a group array.

			if ( isset( $current_meta_value[ $arr_key ] ) && is_numeric( $current_meta_value[ $arr_key ] ) ) {
				// If the array key for the image actually exits and is an int.

				$image_id                       = $current_meta_value[ $arr_key ];
				$current_meta_value[ $arr_key ] = wp_get_attachment_url( $current_meta_value[ $arr_key ] );

				$this->update_row( $current_post_id, $meta_key, $current_meta_value, 'image [within group]' );
			} else {
				// Image ID for this field is either invalid or doesn't exist. This can happen if a group has multiple nested image fields but not all of them are used on a given post.
				error_log( 'Skipping image url field -- No image ID found -- post ID: ' . $current_post_id . '; meta_key ' . $meta_key . '; arr_key: ' . $arr_key );
				return;
			}
		} else {

			/**
			 * If image is standalone.
			 *
			 * @todo need some extra conditionals prior to this. For Affiliate CPT we're occasionally landing here when we shouldn't.
			 */

			$image_id  = $current_meta_value;
			$image_url = wp_get_attachment_url( $current_meta_value );

			$this->update_row( $current_post_id, $meta_key, $image_url, 'image [standalone]' );

		}
	}
}