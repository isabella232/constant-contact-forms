<?php
/**
 * ConstantContact_Display class
 *
 * @package ConstantContactProcessForm
 * @subpackage ConstantContact
 * @author Pluginize
 * @since 1.0.0
 */

/**
 * Class ConstantContact_Display
 */
class ConstantContact_Display {

	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.0.1
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Main wrapper for getting our form display
	 *
	 * @return string Form markup
	 */
	public function form( $form_data ) {

		$response = constant_contact()->process_form->process_wrapper();

		if ( $response ) {
			return wp_kses_post( $response );
		}

		global $wp;

		$return = '<form id="ctct-form" action="' . esc_url( trailingslashit( add_query_arg( '', '', home_url( $wp->request ) ) ) ) . '" method="post">';

		$return .= $this->build_form_fields( $form_data );

		$return .= '<p><input type="submit" name="ctct-submitted" value="' . __( 'Send', 'constantcontact' ) . '"/></p>';
		$return .= wp_nonce_field( 'ctct_submit_form', 'ctct_form', true, false );

		$return .= '</form>';

		return $return;
	}

	/**
	 * Build form fields for shortcode
	 *
	 * @since 1.0.0
	 * @param  array $form_data formulated cmb2 data for form.
	 * @return void
	 */
	public function build_form_fields( $form_data ) {

		// start our wrapper return var
		$return = '';

		// Check to see if we have a description for the form, and display it.
		if (
			isset( $form_data['options'] ) &&
			isset( $form_data['options']['description'] ) &&
			$form_data['options']['description']
		) {
			$return .= $this->description( esc_attr( $form_data['options']['description'] ) );
		}

		// Loop through each of our form fields and output it.
		foreach ( $form_data['fields'] as $key => $value ) {
			$return .= $this->field( $value );
		}

		// Check to see if we have an opt-in for the form, and display it.
		if ( isset( $form_data['options'] ) ) {
			$return .= $this->opt_in( $form_data['options'] );
		}

		return $return;
	}

	/**
	 * Wrapper for single field display
	 *
	 * @author Brad Parbs
	 * @param  array $field field data
	 * @return string        html markup
	 */
	public function field( $field ) {

		// If we don't have a name or a mapping, it will be hard to do things.
		if ( ! isset( $field['name'] ) || ! isset( $field['map_to'] ) ) {
			return;
		}

		// Check all our data points.
		$name  = esc_attr( $field['name'] );
		$map   = esc_attr( $field['map_to'] );
		$desc  = esc_attr( isset( $field['description'] ) ? $field['description'] : '' );
		$type  = esc_attr( isset( $field['type'] ) ? $field['type'] : 'text_field' );
		$req   = isset( $field['required'] ) ? $field['required'] : false;

		// We may have more than one of the same field in our array.
		// this makes sure we keep them unique when processing them.
		$map = $map . '_' . md5( serialize( $field ) );

		// @TODO this could be better
		$required_text = $req ? ' *' : '';

		// @todo clean this
		$return = '<div><p><label>' . esc_attr( $field['name'] ) . esc_attr( $required_text ) . '</label></br>';

		// @todo what the heck is this
		$field_value = ( isset( $_POST[ 'ctct-' . $map ] ) ? esc_attr( $_POST[ 'ctct-' . $map ] ) : '' );

		switch ( $map ) {

			case 'email':
				$return .= '<input type="email" required name="ctct-' . sanitize_title( $map ) . '" value="' . esc_attr( $field_value ) . '"></p></div>';
			break;
			default:
				$return .= '<input type="text" name="ctct-' . sanitize_title( $map ) . '" value="' . esc_attr( $field_value ) . '"></p></div>';
			break;
		}

		return $return;
	}

	/**
	 * Helper method to display form description
	 *
	 * @param  string $description description to outpu
	 * @return echo              echos out form description markup
	 */
	public function description( $description ) {
		echo '<p class="constant-contact constant-contact-form-description">' . esc_attr( $description ) . '</p>';
	}

	/**
	 * Build markup for opt_in form
	 *
	 * @param  array $form_data form data structure
	 * @return string            markup of optin form
	 */
	public function opt_in( $form_data ) {

		if ( ! isset( $form_data['opt_in'] ) || ! isset( $form_data['list'] ) ) {
			return;
		}
		$return = '<div><p>';
		$return .= '<input type="checkbox" id="ctct-opti-in" name="ctct-opti-in" value="' . esc_attr( $form_data['list'] ) . '"/>';

		if ( isset( $form_data['opt_in_instructions'] ) ) {
			$return .= '<label for="ctct-opti-in">' . ' ' . esc_attr( $form_data['opt_in_instructions'] ) . '</label>';
		}

		$return .= '</p></div>';

		return $return;
	}
}

