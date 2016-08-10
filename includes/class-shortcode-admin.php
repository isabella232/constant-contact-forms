<?php
/**
 * Extends WDS_Shortcode_Admin to add our shortcode into a pretty shortcode picker in the editor.
 *
 * @package	ConstantContact
 * @subpackage ConstantContact_Shortcode
 * @author Pluginize
 * @since 1.0.0
 */

if ( class_exists( 'WDS_Shortcodes', false ) && ! class_exists( 'ConstantContact_Shortcode_Admin', false ) ) {

	/**
	 * ConstantContact_Shortcode_Admin
	 *
	 * Sets up shortcode button
	 */
	class ConstantContact_Shortcode_Admin extends WDS_Shortcode_Admin {

		/**
		 * Hooks
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function hooks() {
			add_filter( $this->shortcode . '_shortcode_fields', array( $this, 'filter_shortcode_field' ), 10, 2 );
			parent::hooks();

			add_action( 'save_post', array( $this, 'clear_saved_form_list_transient' ) );
		}

		/**
		 * Array of button data
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function js_button_data() {

			return apply_filters( 'constant_contact_shortcode_button_settings', array(
				'qt_button_text' => __( 'Add Constant Contact Form', 'constantcontact' ),
				'button_tooltip' => __( 'Add Constant Contact Form', 'constantcontact' ),
				'icon'           => 'dashicons-feedback',
				'include_close'  => false,
				'modalClass'     => 'ctct',
				'modalHeight'    => 'auto',
				'modalWidth'     => 500,
			) );
		}

		/**
		 * Adds fields to the button modal using CMB2
		 *
		 * @since 1.0.0
		 * @param array $fields CMB2 fields data.
		 * @param array $button_data Shotcode button data.
		 * @return array $fields
		 */
		public function fields( $fields, $button_data ) {

			$fields[] = array(
				'name'             => __( 'Form Shortcode', 'constantcontact' ),
				'id'               => '_ctct_form',
				'type'             => 'select',
				'show_option_none' => false,
				'options'          => constant_contact()->cpts->get_forms( true ),
			);

			return $fields;
		}

		/**
		 * Filters the data sent to the editor.
		 *
		 * @since 1.0.0
		 * @param array $fields CMB2 fields data.
		 * @param array $shortcode_button Shortcode buttond data.
		 * @return array
		 */
		public function filter_shortcode_field( $fields, $shortcode_button ) {
			if ( ! $shortcode_button instanceof Shortcode_Button ) {
				return $fields;
			}

			$filtered_fields = array();

			if ( isset( $fields['_ctct_form'] ) ) {
				$filtered_fields['form'] = $fields['_ctct_form'];
			}

			return $filtered_fields;
		}

		/**
		 * Delete transient of saved form
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function clear_saved_form_list_transient() {
			delete_transient( 'constant_contact_shortcode_form_list' );
		}
	}
}
