<?php
	/**
	 * Special case: Affiliate CPT 'highlights'. Can't convert via other conversion methods.
	 *
	 * Affiliate CPT: highlights (text field + repeatable group)
	 *
	 * A little complicated. FieldManager 'group' field that contains a single 'text' field as well as a 'repeater' field.
	 * CMB2 doesn't support nested groups/repeater-within-a-group.
	 * Can't use convert_group() fn like we do for other group conversions.
	 *
	 * FM:       ===========================> CMB:
	 * [                                      [
	 *  'meta_key' => 'highlights'              'meta_key' => 'highlight_title_suffix',
	 *  'meta_value' => [                       'meta_value' => text
	 *     'title_suffix' => 'text',          ]
	 *     'highlight' => [                   [
	 *        [                                 'meta_key' => 'highlight',
	 *          'title' => text                 'meta_value' => [
	 *          'url' => text                     [
	 *          'content' => wysiwg                 'title' => text,
	 *        ],                                    'url' => text,
	 *        [                                     'content' => wysiwg
	 *          'title' => text                   ],
	 *          'url' => text                     [
	 *          'content' => wysiwg                 'title' => text,
	 *        ],                                    'url' => text,
	 *     ]                                        'content' => wysiwg
	 *   ]                                        ]
	 * ]                                        ]
	 *                                        ]
	 *
	 * 1) Split title_suffix out into its own meta row
	 * 2) Split the repeater out into its own group
	 **/

/** Unique_Case */
class Unique_Case extends Converter {

	/**
	 * Constructor
	 *
	 * @param string $post_type Post type we're looking at. Can be post/page or custom.
	 * @param string $meta_key  Field name we're converting.
	 */
	public function __construct( $post_type, $meta_key ) {
		error_log( '----------- CONVERTING' );
		error_log( 'FIELD TYPE: Special Case' );
		error_log( 'POST TYPE: ' . $post_type );
		error_log( 'META KEY: ' . $meta_key );

		$this->convert_affiliate__highlights( $meta_key );
	}

	/** Manual/custom code for this specific FM-to-CMB conversion */
	private function convert_affiliate__highlights( $meta_key ) {

		$rows_to_update = $this->generate_sql( $meta_key );

		foreach ( $rows_to_update as $row ) {

			$current_meta_value = maybe_unserialize( $row->meta_value );
			$post_id            = $row->post_id;

			// 1) Need to extract 'title_suffix' into its own meta row
			$title_suffix_value = $current_meta_value['title_suffix'];
			$new_meta_key       = $meta_key . '_title_suffix';

			$this->insert_row( $post_id, $new_meta_key, $title_suffix_value, 'Split from group into standalone' );

			// 2) Extract highlight arr(arr(...repeating fields)).
			// Since CMB is set up so that groups are repeatable by default, this is reflected in the database structure (groups saved as array of arrays).
			// Because of this, we don't need to do the normal FM->CMB data conversion that we need to do for other groups. Pretty neat.

			$current_highlights = $current_meta_value['highlight']; // NOTE: 'highlight' is name/id of repeater array, different from 'highlights' meta_key.
			$this->update_row( $post_id, $meta_key, $current_highlights, 'Repeater field' );

		}

	}


}