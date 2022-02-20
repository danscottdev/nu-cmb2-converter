<?php
/**
 * Handle data conversion for migrating NU sites from FieldManager to CMB2
 * Needed for instances of field types: Group, Image, Checkbox, Repeater [maybe more]
 */

/** FM_TO_CMB database converter */
class FM_TO_CMB {

	/**
	 * Instance of this class
	 *
	 * @var boolean
	 */
	public static $instance = false;

	/**
	 * Construct
	 *
	 * This functionality hooks into CMB2 activation.
	 * When we activate CMB2 for the first time, this plugin will take all of our existing FieldManager data and convert it to a CMB2-compatiable format.
	 * */
	public function __construct() {
		register_activation_hook( 'cmb2/init.php', [ $this, 'fm_to_cmb_database_conversion' ] );
	}

	/** Singleton */
	public static function singleton() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/** Main function -- fires on CMB2 Plugin Activation */
	public function fm_to_cmb_database_conversion() {
		include plugin_dir_path( __FILE__ ) . '/converter/class-converter.php';
		$converter = Converter::singleton();

		/**
		 * For each instance of needed data conversion, fire the following method:
		 * $converter->convert( POST_TYPE, FIELD_NAME, FIELD_TYPE, [ARR_KEY] )
		 *
		 * **IMPORTANT** If an 'image' field is nested within a parent 'group' field:
		 * - FIELD_NAME needs to be the name of the group field
		 * - ARR_KEY needs to be the name of the image field that's within the group array.
		 * - Image conversion needs to be performed BEFORE group conversion.
		 *
		 * Not needed for basic text fields.
		 * Data conversion is used pretty heavily below because NUSystem.org keeps almost all custom fields within a parent 'group' field.
		 * Most other NU-branded sites should be easier.
		 *
		 *
		 * @todo After refactoring, I don't think we actually need to pass POST_TYPE anymore...? I don't think our convert() function cares about post type anymore.
		 */


	// ### AFFILIATES CPT
			$converter->convert( 'affiliate', 'external_url', 'group' );
			$converter->convert( 'affiliate', 'featured', 'checkbox' );

			/**
			 * Note: "logo" is a group containing the image path + alt text fields.
			 * Image path is saved as a string, rather than a file upload, presumably because the logos are SVGs and WP doesn't like those being uploaded.
			 * Thus, no 'image' conversion needed.
			 */
			$converter->convert( 'affiliate', 'logo', 'group' );

			$converter->convert( 'affiliate', 'homepage_info', 'image', 'img' );
			$converter->convert( 'affiliate', 'homepage_info', 'group' );
			$converter->convert( 'affiliate', 'hero', 'image', 'mobile' );
			$converter->convert( 'affiliate', 'hero', 'group' );
			$converter->convert( 'affiliate', 'content', 'image', 'img_1' );
			$converter->convert( 'affiliate', 'content', 'image', 'img_2' );
			$converter->convert( 'affiliate', 'content', 'group' );
			$converter->convert( 'affiliate', 'fast_facts', 'group' );
			$converter->convert( 'affiliate', 'president', 'image', 'img' );
			$converter->convert( 'affiliate', 'president', 'group' );


			/**
			 * 'Highlights' is a bit complicated, largely due to it having a repeater field nested within a group field, along with other fields.
			 * The data-conversion function (found in class-unique-cases.php) is hardcoded for this specific case.
			 * I don't think we use many repeaters elsewhere on NU-related sites, so probably not worth the effort to try and make it reusable/generic.
			 */
			$converter->convert( 'affiliate', 'highlights', 'SPECIAL' );


	// ### LEADERSHIP CPT
			$converter->convert( 'leadership', 'tall_img', 'image' );
			// No conversion needed for 'title & 'subtitle' fields -> basic text fields.


	// ### PAGES
			$converter->convert( 'page', 'mobile_hero', 'image' );


	// ### GRAVITY FORMS
			/**
			 * Gravity forms (and thus our field customizations for it) aren't used on nusystem.org.
			 * Leaving this line here as a reminder to self that I already looked into it.
			 * Will need to build out for other NU-sites though.
			 */

	}

}
