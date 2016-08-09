<?php

/**
 * ConstantContact_Display class
 *
 * @package ConstantContact_Display
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
	 * Scripts
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function scripts() {

		// Check if we are in debug mode. allow
		$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? true : false;

		// based on our debug mode, potentially add a min prefix
		$suffix = ( true === $debug ) ? '' : '.min';

		// Register our javascript file.
		wp_register_script(
			'ctct_frontend_forms',
			constant_contact()->url() . 'assets/js/ctct-plugin-frontend' . $suffix . '.js',
			array(),
			constant_contact()->version,
			true
		);
	}

	/**
	 * Main wrapper for getting our form display
	 *
	 * @since  1.0.0
	 * @return string Form markup
	 */
	public function form( $form_data, $form_id = '', $skip_styles = false ) {

		// Also enqueue our scripts
		$this->scripts();

		// Conditionally enqueue our styles
		if ( ! $skip_styles ) {

			wp_enqueue_style(
				'ctct_form_styles',
				constant_contact()->url() . 'assets/css/style.css',
				array(),
				constant_contact()->version
			);

			// Enqueued script.
			wp_enqueue_script( 'ctct_frontend_forms' );
		}

		// Start our return markup and some default variables
		$return           = '';
		$form_err_display = '';
		$error_message    = false;
		$status           = false;

		// Get a potential response from our processing wrapper
		// This returns an array that looks like this:
		// array(
		// 'status'  => $status,
		// 'message' => $message,
		// )
		// if the status is success, then we sent the form correctly
		// if the status is error, then we will re-show the form, but also
		// with our error messages.
		$response = constant_contact()->process_form->process_wrapper( $form_data, $form_id );

		// submitted values
		$old_values = isset( $response['values'] ) ? $response['values'] : '';
		$req_errors = isset( $response['errors'] ) ? $response['errors'] : '';

		// Check to see if we got a response, and if it has the fields we expect
		if ( $response && isset( $response['message'] ) && isset( $response['status'] ) ) {

			// If we were succesful, then display success message
			if ( 'success' == $response['status'] ) {

				// If we were successful, we'll return here so we don't display the entire form again
				return $this->message( 'success', $response['message'] );

			} else {

				// If we didn't get a success message, then we want to error.
				// We already checked for a messsage response, but we'll force the
				// status to error if we're not here
				$status = 'error';
				$error_message = trim( $response['message'] );
			}
		}

		// If we got an error for our status, and we have an error message, display it.
		if ( 'error' == $status || $error_message ) {

			if ( ! empty( $error_message ) ) {
				// We'll show this error right inside our form
				$form_err_display = $this->message( 'error', $error_message );
			}
		}

		// Force uniqueness of an id for the form
		// @todo figure out another way to do this maybe?
		$rf_id = 'ctct-form-' . mt_rand();

		// Build out our form
		$return .= '<form class="ctct-form" id=' . $rf_id . ' action="' . esc_url( $this->get_current_page() ) . '" method="post">';

		// If we have errors, display them
		$return .= $form_err_display;

		// Output our normal form fields
		$return .= $this->build_form_fields( $form_data, $old_values, $req_errors );

		// Add our hidden verification fields
		$return .= $this->add_verify_fields( $form_data );

		// Add our submit field
		$return .= $this->submit();

		// Nonce the field too
		$return .= wp_nonce_field( 'ctct_submit_form', 'ctct_form', true, false );

		// Close our form
		$return .= '</form>';

		$return .= '<script type="text/javascript">';
		$return .= 'var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";';
		$return .= '</script>';

		// Return it all
		return $return;
	}

	/**
	 * Get our current URL in a somewhat robust way
	 *
	 * @since  1.0.0
	 * @return string url of current page
	 */
	public function get_current_page() {
		global $wp;
		return trailingslashit( add_query_arg( '', '', home_url( $wp->request ) ) );
	}

	/**
	 * Adds hidden input fields to our form for form id and verify id
	 *
	 * @since  1.0.0
	 * @param  string $form_data html markup
	 */
	public function add_verify_fields( $form_data ) {

		// Sanity check
		if (
			isset( $form_data ) &&
			isset( $form_data['options'] ) &&
			isset( $form_data['options']['form_id'] )
		) {

			// sanitize our form id
			$form_id = absint( $form_data['options']['form_id'] );

			// sanity check on our form id
			if ( ! $form_id ) {
				return false;
			}

			// Add hidden field with our form id in it
			$return = $this->input( 'hidden', 'ctct-id', 'ctct-id', $form_id, '', '', true );

			// if we have saved a verify value, add that to our field as well. this is to double-check
			// that we have the correct form id for processing later
			$verify_key = get_post_meta( $form_id, '_ctct_verify_key', true );

			if ( $verify_key ) {
				$return .= $this->input( 'hidden', 'ctct-verify', 'ctct-verify', $verify_key, '', '', true );
			}

			return $return;
		}
	}

	/**
	 * Build form fields for shortcode
	 *
	 * @since 1.0.0
	 * @param  array $form_data formulated cmb2 data for form.
	 * @return void
	 */
	public function build_form_fields( $form_data, $old_values, $req_errors ) {

		// start our wrapper return var
		$return = '';

		// Check to see if we have a form ID for the form, and display our description.
		if ( isset( $form_data['options'] ) && isset( $form_data['options']['form_id'] ) ) {

			// Get our description
			$desc = isset( $form_data['options']['description'] ) ? $form_data['options']['description'] : '';

			// Clean our form ID
			$form_id = absint( $form_data['options']['form_id'] );

			// Add in our Description
			$return .= $this->description( $desc, $form_id );
		}

		// Loop through each of our form fields and output it.
		foreach ( $form_data['fields'] as $key => $value ) {
			$return .= $this->field( $value, $old_values, $req_errors );
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
	 * @since  1.0.0
	 * @param  array $field field data
	 * @return string        html markup
	 */
	public function field( $field, $old_values = array(), $req_errors = array() ) {

		// If we don't have a name or a mapping, it will be hard to do things.
		if ( ! isset( $field['name'] ) || ! isset( $field['map_to'] ) ) {
			return;
		}

		$field = wp_parse_args( $field, array(
			'name'        => '',
			'map_to'      => '',
			'type'        => '',
			'description' => '',
			'required'    => false,
		) );

		// Check all our data points.
		$name   = sanitize_text_field( $field['name'] );
		$map    = sanitize_text_field( $field['map_to'] );
		$desc   = sanitize_text_field( isset( $field['description'] ) ? $field['description'] : '' );
		$type   = sanitize_text_field( isset( $field['type'] ) ? $field['type'] : 'text_field' );
		$value  = sanitize_text_field( isset( $field['value'] ) ? $field['value'] : false );
		$req    = isset( $field['required'] ) ? $field['required'] : false;

		// We may have more than one of the same field in our array.
		// this makes sure we keep them unique when processing them.
		if ( 'submit' != $type ) {
			$map = $map . '___' . md5( serialize( $field ) );
		}

		// Default error status
		$field_error = false;

		// If we got any errors, then pass them through to the form field
		if ( ! empty( $req_errors ) ) {

			// Loop through each error
			foreach ( $req_errors as $error ) {

				// Make sure we have a field ID and an actual error
				if ( isset( $error['id'] ) && isset( $error['error'] ) ) {

					// If the error matches the field we're rendering
					if ( $map == $error['id'] ) {

						// Start our field error return
						$field_error = '<span class="ctct-field-error">';

						// Based on the error type, display an error.
						if ( 'invalid' == $error['error'] ) {
							 $field_error .= __( 'Error: Please correct your entry.', 'constantcontact' );
						} else {
							$field_error .= __( ' Error: Please fill out this field.', 'constantcontact' );
						}

						// Finish error return
						$field_error .= '</span>';
					}
				}
			}
		}

		// Potentially replace value with submitted value
		$value = $this->get_submitted_value( $value, $map, $field, $old_values );

		// Based on our type, output different things
		switch ( $type ) {
			case 'custom':
			case 'first_name':
			case 'last_name':
			case 'phone_number':
			case 'job_title':
			case 'company':
			case 'website':
			case 'text_field':
				return $this->input( 'text', $name, $map, $value, $desc, $req, false, $field_error );
				break;
			case 'custom_text_area':
				return $this->textarea( $name, $map, $value, $desc, $req, false, $field_error );
				break;
			case 'email':
				return $this->input( 'email', $name, $map, $value, $desc, $req, false, $field_error );
				break;
			case 'hidden':
				return $this->input( 'hidden', $name, $map, $value, $desc, $req );
				break;
			case 'checkbox':
				return $this->checkbox( $name, $map, $value, $desc );
				break;
			case 'submit':
				return $this->input( 'submit', $name, $map, $value, $desc, $req, false, $field_error );
				break;
			case 'address':
				return $this->address( $name, $map, $value, $desc, $req, $field_error );
				break;
			case 'anniversery':
			case 'birthday':
				// need this to be month / day / year
				return $this->dates( $name, $map, $value, $desc, $req, false, $field_error );
				break;
			default:
				return $this->input( 'text', $name, $map, $value, $desc, $req, false, $field_error );
				break;
		}
	}

	/**
	 * Gets submitted values
	 *
	 * @since  1.0.0
	 * @param  array $field field data
	 * @return string        submitted value
	 */
	public function get_submitted_value( $value = '', $map = '', $field = array(), $submitted_vals = array() ) {

		// If we have a value already return it
		if ( $value ) {
			return $value;
		}

		// Sanity check
		if ( ! is_array( $submitted_vals ) ) {
			return '';
		}

		// Possible return this array
		$return = array();

		// Loop through each val and try to grab our submitted
		foreach ( $submitted_vals as $post ) {

			// Make sure we have some value in the submitted
			if ( isset( $post['key'] ) && $post['key'] ) {

				// If we have an address, its a special case
				if ( 'address' == $field['name'] ) {

					// If any of our keys contain our address breaker, then add
					// it to the array
					if ( strpos( $post['key'], '_address___' ) !== false ) {

						// Try to grab the street_address (etc) part of our key
						$addr_key = explode( '___', $post['key'] );

						// If we got something, add it to our return array
						if ( isset( $addr_key[0] ) && $addr_key[0] ) {
							$return[ esc_attr( $addr_key[0] ) ] = sanitize_text_field( $_POST[ esc_attr( $post['key'] ) ] );
						}
					}

					// Otherwise make sure we have a value
				} elseif ( $post['key'] == $map && isset( $_POST[ esc_attr( $map ) ] ) ) {
					// Clean and return
					return sanitize_text_field( $_POST[ esc_attr( $map ) ] );
				}
			}
		}

		// If we did add to our array, send it back
		if ( ! empty( $return ) ) {
			return $return;
		}

		return '';
	}

	/**
	 * Helper method to display in-line for success/error messages
	 *
	 * @since  1.0.0
	 * @param  string $type    success / error / etc for class
	 * @param  string $message message to display to user
	 * @return string          html markup
	 */
	public function message( $type, $message ) {
		return '<p class="ctct-message ' . esc_attr( $type ) . '">' . esc_attr( $message ) . '</p>';
	}

	/**
	 * Helper method to display form description
	 *
	 * @since  1.0.0
	 * @param  string $description description to outpu
	 * @return echo              echos out form description markup
	 */
	public function description( $desc = '', $form_id = false ) {

		// Set default var
		$display = '';

		// if we have the permissions, also display an edit link
		if ( current_user_can( 'edit_posts' ) && $form_id ) {

			// get our edit link
			$edit_link = get_edit_post_link( absint( $form_id ) );

			// if we got a link, display it
			if ( $edit_link ) {
				$display .= '<a class="button ctct-button" href="' . esc_url( $edit_link ) . '">' . __( 'Edit Form', 'constantcontact' ) . '</a>';
			}
		}

		// Display our description
		return '<span class="ctct-form-description">' . wpautop( wp_kses_post( $desc ) ) . '</span>' . $display;

	}

	/**
	 * Helper method to display label for form field + field starting markup
	 *
	 * @since  1.0.0
	 * @param  string  $type  type of field
	 * @param  string  $name  name / id of field
	 * @param  string  $label label text for field
	 * @param  boolean $req   is this field required?
	 * @return string         HTML markup
	 */
	public function field_top( $type = '', $name = '', $f_id = '', $label = '', $req = false, $use_label = true ) {

		// Set blank defaults for required info
		$req_label = '';

		// If this is required, we output the HMTL5 required att
		if ( $req ) {
			$req_label = apply_filters( 'constant_contact_required_label', '<abbr title="required">*</abbr>' );
		}

		// Start building our return markup
		$markup = '<p class="ctct-form-field ctct-form-field-' . $type . '">';

		// alow skipping label, also don't show for submit buttons
		if ( $use_label && ( 'submit' != $type ) && ( 'hidden' != $type ) ) {

			// Our field label will be the form name + required asterisk + our label
			$markup .= $this->get_label( $f_id, $name . ' ' . $req_label );
		}

		// If we're not on submit or hidden, but still doing label on bottom,
		// then output a container div
		if ( ! $use_label ) {
			$markup .= '<div class="ctct-input-container">';
		}

		// return it
		return $markup;
	}

	/**
	 * Bottom of field markup
	 *
	 * @since  1.0.0
	 * @return string HTML markup
	 */
	public function field_bottom( $name = '', $field_label = '' ) {

		$markup = '';
		if ( ! empty( $name ) && ! empty( $field_label ) ) {
			$markup .= $this->get_label( $name, $field_label ) . '</div>';
			;
		}
		// Finish building our markup
		return $markup . '</p>';
	}

	/**
	 * Helper method to get form label
	 *
	 * @since  1.0.0
	 * @param  string $name name/id of form field
	 * @param  string $text text to display as label
	 * @return string       HTML markup
	 */
	public function get_label( $f_id, $field_label ) {
		return '<label for="' . $f_id . '">' . $field_label . '</label>';
	}

	/**
	 * Wrapper for 'input' form fields
	 *
	 * @since  1.0.0
	 * @param  string  $type   type of form field
	 * @param  string  $name   ID of form field
	 * @param  string  $value  pre-filled value
	 * @param  string  $label  label text for inpug
	 * @param  boolean $req    is this field required?
	 * @param  boolean $f_only should we only return the field itself, with no label?
	 * @return string          HTML markup for field
	 */
	public function input( $type = 'text', $name = '', $id = '', $value = '', $label = '', $req = false, $f_only = false, $field_error = false ) {

		// Sanitize our stuff / set values
		$name  = sanitize_text_field( $name );
		$f_id  = sanitize_title( $id );
		$type  = sanitize_text_field( $type );
		$value = sanitize_text_field( $value );
		$label = sanitize_text_field( $label );
		$req_text = $req ? 'required' : '';

		// Start our markup
		$markup = $this->field_top( $type, $name, $f_id, $label, $req );

		// Set our field as as seprate var, because we allow for only returning that
		$field = '<input ' . $req_text . ' type="' . $type . '" name="' . $f_id . '" id="' . $f_id . '" value="' . $value . '" placeholder="' . $label . '"/>';

		// If we have an error
		if ( $field_error ) {

			// Tack that sucker on to the end of our input
			$field = str_replace( '/>', 'class="ctct-invalid />', $field );
		}

		// Add our field to our markup
		$markup .= $field;

		// If we got an error, add it to the bottom label
		if ( $field_error ) {
			$markup .= $this->field_bottom( $id, $field_error );
		} else {
			$markup .= $this->field_bottom();
		}

		// If we passed in a flag for only the field, just return that
		if ( $f_only ) {
			return $field;
		}

		// Otherwise all the markup
		return $markup;
	}

	/**
	 * Checkbox field helper method
	 *
	 * @since  1.0.0
	 * @param  string $name  name/it of field
	 * @param  string $value value of field
	 * @param  string $label label / desc text
	 * @return string        html markup for checkbox
	 */
	public function checkbox( $name = '', $f_id = '', $value = '', $label = '' ) {

		// Clean our inputs
		$name  = sanitize_text_field( $name );
		$f_id  = sanitize_title( $f_id );
		$value = sanitize_text_field( $value );
		$label = esc_attr( $label );
		$type = 'checkbox';

		// Build up our markup
		$markup = $this->field_top( $type, $name, $f_id, $label, false, false );
		$markup .= '<input type="' . $type . '" name="' . $f_id . '" id="' . $f_id . '" value="' . $value . '" />';
		$markup .= $this->field_bottom( $name, ' ' . $label );

		// return it
		return $markup;
	}

	/**
	 * Helper method for submit button
	 *
	 * @since  1.0.0
	 * @return string html markup
	 */
	public function submit() {
		return $this->field( array(
			'type'   => 'submit',
			'name'   => 'ctct-submitted',
			'map_to' => 'ctct-submitted',
			'value'  => __( 'Send', 'constantcontact' ),
		) );
	}

	/**
	 * Build markup for opt_in form
	 *
	 * @since  1.0.0
	 * @param  array $form_data form data structure
	 * @return string            markup of optin form
	 */
	public function opt_in( $form_data ) {

		// Make sure we have our optin data
		if ( ! isset( $form_data['optin'] ) ) {
			return;
		}

		// Set up our defaults
		$optin = wp_parse_args( $form_data['optin'], array(
			'opt_in'              => false,
			'opt_in_instructions' => '',
			'list'                => false,
		) );

		// Make sure we have our opt in set, as well as an associated list
		if (
			isset( $optin['opt_in'] ) &&
			$optin['opt_in'] &&
			isset( $optin['list'] ) &&
			$optin['list']
		) {
			return $this->optin_display( $optin );
		}
	}

	/**
	 * Internal method to display checkbox
	 *
	 * @since  1.0.1
	 * @param  array $optin optin data
	 * @return string        html markup
	 */
	public function optin_display( $optin ) {
//@TODO modify here for new opt in methods
		// Clean our inputs, set defaults
		$label   = sanitize_text_field( isset( $optin['opt_in_instructions'] ) ? $optin['opt_in_instructions'] : '' );
		$hide    = isset( $optin['opt_in_hide'] ) ? $optin['opt_in_hide'] : false;
		$value   = sanitize_text_field( isset( $optin['list'] ) ? $optin['list'] : '' );

		// Start our markup return
		$markup = '';
		// If we set to hide the field, then hide it inline
		if ( $hide ) {
			$markup = '<div class="ctct-optin-hide" style="display:none;">';
		}

		// Build up our markup
		$markup .= $this->field_top( 'checkbox', 'ctct-opt-in', 'ctct-opt-in', $label, false, false );
		$markup .= '<input type="checkbox" name="ctct-opt-in" id="ctct-opt-in" value="' . $value . '" />';
		$markup .= $this->field_bottom( 'ctct-opt-in', ' ' . $label );

		// If we set to hide, close our open div
		if ( $hide ) {
			$markup .= '</div><!--.ctct-optin-hide -->';
		}

		// return it
		return $markup;
	}

	/**
	 * Builds a fancy address field group
	 *
	 * @since  1.0.0
	 * @param  string $name  name of fields
	 * @param  string $f_id  form id name
	 * @param  array  $value values of each field
	 * @param  string $desc  label of field
	 * @return string        html markup of field
	 */
	public function address( $name = '', $f_id = '', $value = array(), $desc = '', $req = false, $field_error = '' ) {

		// Set up our text strings
		$street = __( 'Street Address', 'constantcontact' );
		$line_2 = __( 'Address Line 2', 'constantcontact' );
		$city   = __( 'City', 'constantcontact' );
		$state  = __( 'State', 'constantcontact' );
		$zip    = __( 'ZIP Code', 'constantcontact' );

		// Set our values
		$v_street = isset( $value['street_address'] ) ? $value['street_address'] : '';
		$v_line_2 = isset( $value['line_2_address'] ) ? $value['line_2_address'] : '';
		$v_city   = isset( $value['city_address'] ) ? $value['city_address'] : '';
		$v_state  = isset( $value['state_address'] ) ? $value['state_address'] : '';
		$v_zip    = isset( $value['zip_address'] ) ? $value['zip'] : '';

		$req = $req ? ' required ' : '';

		// Build our field
		$return  = '<p class="ctct-address"><fieldset>';
		$return .= ' <legend>' . esc_attr( $name ) . '</legend>';
		$return .= ' <div class="ctct-form-field ctct-field-full address-line-1">';
		$return .= '  <label for="street_' . esc_attr( $f_id ) . '">' . esc_attr( $street ) . '</label>';
		$return .= '  <input ' . $req . 'type="text" name="street_' . esc_attr( $f_id ) . '" id="street_' . esc_attr( $f_id ) . '" value="' . esc_attr( $v_street ) . '">';
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-full address-line-2" id="input_2_1_2_container">';
		$return .= '  <label for="line_2_' . esc_attr( $f_id ) . '">' . esc_attr( $line_2 ) . '</label>';
		$return .= '  <input ' . $req . 'type="text" name="line_2_' . esc_attr( $f_id ) . '" id="line_2_' . esc_attr( $f_id ) . '" value="' . esc_attr( $v_line_2 ) . '">';
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-third address-city" id="input_2_1_3_container">';
		$return .= '  <label for="city_' . esc_attr( $f_id ) . '">' . esc_attr( $city ) . '</label>';
		$return .= '  <input ' . $req . 'type="text" name="city_' . esc_attr( $f_id ) . '" id="city_' . esc_attr( $f_id ) . '" value="' . esc_attr( $v_city ) . '">';
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-third address-state" id="input_2_1_4_container">';
		$return .= '  <label for="state_' . esc_attr( $f_id ) . '">' . esc_attr( $state ) . '</label>';
		$return .= '  <input ' . $req . 'type="text" name="state_' . esc_attr( $f_id ) . '" id="state_' . esc_attr( $f_id ) . '" value="' . esc_attr( $v_state ) . '">';
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-third address-zip" id="input_2_1_5_container">';
		$return .= '  <label for="zip_' . esc_attr( $f_id ) . '">' . esc_attr( $zip ) . '</label>';
		$return .= '  <input ' . $req . 'type="text" name="zip_' . esc_attr( $f_id ) . '" id="zip_' . esc_attr( $f_id ) . '" value="' . esc_attr( $v_zip ) . '">';
		$return .= ' </div>';
		$return .= '</fieldset></p>';

		return $return;
	}

	/**
	 * Gets and return a 3-part date selector
	 *
	 * @since  1.0.1
	 * @param  string  $name        name of field
	 * @param  string  $f_id        field id
	 * @param  array   $value       values to pre-fill
	 * @param  string  $desc        description of fields
	 * @param  boolean $req         is required?
	 * @param  string  $field_error field error text
	 * @return string               html markup of fields
	 */
	public function dates( $name = '', $f_id = '', $value = array(), $desc = '', $req = false, $field_error = '' ) {

		// Set our field lables
		$month = __( 'Month', 'constantcontact' );
		$day   = __( 'Day', 'constantcontact' );
		$year  = __( 'Year', 'constantcontact' );

		// @TODO these need to get set correctly
		// Set our values
		$v_month = isset( $value['month'] ) ? $value['month'] : '';
		$v_day   = isset( $value['day'] ) ? $value['day'] : '';
		$v_year  = isset( $value['year'] ) ? $value['year'] : '';

		// Build our field
		$return  = '<p class="ctct-date"><fieldset>';
		$return .= ' <legend>' . esc_attr( $name ) . '</legend>';
		$return .= ' <div class="ctct-form-field ctct-field-inline month">';
		$return .= $this->get_date_dropdown( $month, $f_id, 'month', $v_month, $req );
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-inline day">';
		$return .= $this->get_date_dropdown( $day, $f_id, 'day', $v_day, $req );
		$return .= ' </div>';
		$return .= ' <div class="ctct-form-field ctct-field-inline year">';
		$return .= $this->get_date_dropdown( $year, $f_id, 'year', $v_year, $req );
		$return .= ' </div>';

		$return .= '</fieldset></p>';

		return $return;
	}

	/**
	 * Gets actual dropdowns for date selector
	 *
	 * @since  1.0.1
	 * @param  string  $text           text for default option
	 * @param  string  $f_id           field id
	 * @param  string  $type           type of dropdown (day, month, year)
	 * @param  string  $selected_value previous value
	 * @param  boolean $req            is require?
	 * @return string                  markup of field
	 */
	public function get_date_dropdown( $text = '', $f_id = '', $type = '', $selected_value = '', $req = false ) {

		// Account for our weird IDs
		$f_id = str_replace( 'birthday', 'birthday_' . $type, $f_id );
		$f_id = str_replace( 'anniversary', 'anniversary_' . $type, $f_id );

		// Start our field
		$return = '<select name="' . esc_attr( $f_id ) . '" class="ctct-date-select ctct-date-select-' . esc_attr( $type ) . '">';

		if ( $req ) {
			$return = str_replace( '">', '" required>', $return );
		}

		// Grab all of our options based on the field type
		$return .= $this->get_date_options( $text, $this->get_date_values( $type ), $selected_value );

		// Close our field
		$return .= '</select>';

		// Send it back
		return $return;
	}

	/**
	 * Gets option markup for a date selector
	 *
	 * @since  1.0.1
	 * @param  string $text                 default first option
	 * @param  array  $values               values to use
	 * @param  array  $prev_selected_values previous selected values
	 * @return string                       html markup
	 */
	public function get_date_options( $text = '', $values = array(), $prev_selected_values = array() ) {

		// First, we'll want
		$return = '<option value="">' . sanitize_text_field( $text ) . '</option>';

		// If we don't have a values array, bail out
		if ( ! is_array( $values ) ) {
			return $return;
		}

		// Loop through each of our values
		foreach ( $values as $key => $value ) {

			// Sanitize and make sure our key is set
			$key = sanitize_text_field( isset( $key ) ? $key : '' );

			// Sanitize and make sure our value is set
			$value = sanitize_text_field( isset( $value ) ? $value : '' );

			// Build out our option value
			$return .= '<option value="' . $key . '">' . $value . '</option>';
		}

		// Send it back
		return $return;
	}

	/**
	 * Gets array of data for a date dropdown type
	 *
	 * @since  1.0.1
	 * @param  string $type day,month,or,year
	 * @return array       array of data
	 */
	public function get_date_values( $type ) {

		// Based on $type, we'll send back an array of either days, months, or years
		switch ( $type ) {
			case 'day':
				$return = apply_filters( 'constant_contact_dates_day', $this->get_days() );
				break;
			case 'month':
				$return = apply_filters( 'constant_contact_dates_month', array(
					'january'   => __( 'January', 'contantcontact' ),
					'february'  => __( 'February', 'contantcontact' ),
					'march'     => __( 'March', 'contantcontact' ),
					'april'     => __( 'April', 'contantcontact' ),
					'may'       => __( 'May', 'contantcontact' ),
					'june'      => __( 'June', 'contantcontact' ),
					'july '     => __( 'July ', 'contantcontact' ),
					'august'    => __( 'August', 'contantcontact' ),
					'september' => __( 'September', 'contantcontact' ),
					'october'   => __( 'October', 'contantcontact' ),
					'november'  => __( 'November', 'contantcontact' ),
					'december'  => __( 'December', 'contantcontact' ),
				) );
				break;
			case 'year':
				$return = apply_filters( 'constant_contact_dates_year', $this->get_years() );
				break;
		}

		return $return;
	}

	/**
	 * Helper method to get all years
	 *
	 * @since  1.0.1
	 * @return array years from 1910-current year
	 */
	public function get_years() {

		// Get all of our years
		$year_range = range( 1910,  date( 'Y' ) );

		$year_range = array_reverse( $year_range );

		// Loop through each of the years we  have
		foreach ( $year_range as $year ) {
			$years[ $year ] = $year;
		}

		return $years;
	}

	/**
	 * Gets array of 1-31
	 *
	 * @since  1.0.1
	 * @return array array of days
	 */
	public function get_days() {

		// Get all of our day
		$day_range = range( 1, 31 );

		// Loop through each of the days we  have
		foreach ( $day_range as $day ) {
			$days[ $day ] = $day;
		}

		return $days;
	}

	/**
	 * Displays text area field
	 *
	 * @param  string $name        name of field
	 * @param  string $map         id of field
	 * @param  string $value       previous value of field
	 * @param  string $desc        description/label of field
	 * @param  boolean $req         is required?
	 * @param  string $field_error error from field
	 * @return string              html markup
	 */
	public function textarea( $name = '', $map = '', $value = '', $desc = '', $req = false, $field_error = '' ) {
		// Set our required text
		$req_text = $req ? 'required' : '';

		// If required, get our label
		$req_label = '';
		if ( $req ) {
			$req_label = apply_filters( 'constant_contact_required_label', '<abbr title="required">*</abbr>' );
		}

		// Build up our field markup
		$return  = '<p><label for="' . esc_attr( $map ) . '">' . esc_attr( $name ) . ' ' . $req_label . '</label><textarea ' . $req_text . ' name="' . esc_attr( $map ) . '" placeholder="' . esc_attr( $desc ) . '">' . esc_html( $value ) . '</textarea>';

		// IF we have an error, add it to our markup
		if ( $field_error ) {
			$return .= '<span class="ctct-field-error"><label for="' . esc_attr( $map ) . '">' . esc_attr( __( 'Error: Please correct your entry.', 'constantcontact' ) ) . '</label></span>';
		}

		// Send it back
		return $return . '</p>';
	}
}

