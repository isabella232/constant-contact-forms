<?php
/**
 * Builder fields.
 *
 * @package ConstantContact
 * @subpackage BuilderFields
 * @author Constant Contact
 * @since 1.0.0
 */

/**
 * Helper class for dealing with our form builder field functionality.
 *
 * @since 1.0.0
 */
class ConstantContact_Builder_Fields {

	/**
	 * Parent plugin class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $plugin = null;

	/**
	 * Prefix for our meta fields/boxes.
	 *
	 * @var string
	 */
	public $prefix = '_ctct_';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object $plugin Parent class object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->init();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'init', array( $this, 'hooks' ) );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		global $pagenow;

		/**
		 * Filters the pages to add our form builder content to.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value Array of WP admin pages to load builder on.
		 */
		$form_builder_pages = apply_filters(
			'constant_contact_form_builder_pages',
			array( 'post-new.php', 'post.php' )
		);

		// Only load the cmb2 fields on our specified pages.
		if ( $pagenow && in_array( $pagenow, $form_builder_pages, true ) ) {

			add_action( 'cmb2_admin_init', array( $this, 'description_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'constant_contact_list_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'opt_ins_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'generated_shortcode' ) );
			add_action( 'cmb2_admin_init', array( $this, 'email_settings' ) );
			add_action( 'cmb2_admin_init', array( $this, 'custom_form_css_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'custom_input_css_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'fields_metabox' ) );
			add_action( 'cmb2_admin_init', array( $this, 'add_css_reset_metabox' ) );
			add_filter( 'cmb2_override__ctct_generated_shortcode_meta_save', '__return_empty_string' );
			add_action( 'cmb2_render_reset_css_button', array( $this, 'render_reset_css_button' ) );
		}

	}

	public function constant_contact_list_metabox() {

		if ( constant_contact()->api->is_connected() ) {
			$list_metabox = new_cmb2_box( array(
				'id'           => 'ctct_0_list_metabox',
				'title'        => __( 'Constant Contact List', 'constant-contact-forms' ),
				'object_types' => array( 'ctct_forms' ),
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			) );

			// Get our lists.
			$lists = $this->plugin->builder->get_lists();

			// Add field if conncted to API.
			if ( $lists ) {

				// Allow choosing a list to add to.
				$list_metabox->add_field( array(
					'name'             => __( 'Add subscribers to', 'constant-contact-forms' ),
					'id'               => $this->prefix . 'list',
					'type'             => 'select',
					'show_option_none' => __( 'No List Selected', 'constant-contact-forms' ),
					'default'          => 'none',
					'options'          => $lists,
				) );
			}
		}
	}

	/**
	 * Form description CMB2 metabox.
	 *
	 * @since 1.0.0
	 */
	public function description_metabox() {

		/**
		 * Initiate the $description_metabox.
		 */
		$description_metabox = new_cmb2_box( array(
			'id'           => 'ctct_0_description_metabox',
			'title'        => __( 'Form Description', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		$description_metabox->add_field( array(
			'description' => __( 'This message will display above the form fields, so use it as an opportunity to pitch your email list. Tell visitors why they should subscribe to your emails, focusing on benefits like insider tips, discounts, subscriber coupons, and more.', 'constant-contact-forms' ),
			'id'   => $this->prefix . 'description',
			'type' => 'wysiwyg',
			'options' => array(
				'media_buttons' => false,
				'textarea_rows' => '5',
				'teeny'         => false,
			),
		) );
	}

	/**
	 * Form options CMB2 metabox.
	 *
	 * @since 1.0.0
	 */
	public function opt_ins_metabox() {

		$options_metabox = new_cmb2_box( array(
			'id'           => 'ctct_1_optin_metabox',
			'title'        => __( 'Form Options', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		$options_metabox->add_field( array(
			'name'    => __( 'Button text', 'constant-contact-forms' ),
			'id'      => $this->prefix . 'button_text',
			'type'    => 'text',
			'default' => esc_attr__( 'Sign up', 'constant-contact-forms' ),
		) );

		$options_metabox->add_field( array(
			'name'    => __( 'Success message', 'constant-contact-forms' ),
			'id'      => $this->prefix . 'form_submission_success',
			'type'    => 'text',
			'default' => esc_attr__( 'Your information has been submitted', 'constant-contact-forms' ),
		) );

		$options_metabox->add_field( array(
			'name'  => esc_html__( 'Submission behavior', 'constant-contact-forms' ),
			'type'  => 'title',
			'id'    => 'submission_behavior_title',
			'after' => '<hr/>',
		) );

		$options_metabox->add_field( array(
			'name'            => __( 'Redirect URL', 'constant-contact-forms' ),
			'id'              => $this->prefix . 'redirect_uri',
			'type'            => 'text',
			'description'     => esc_html__( 'Leave blank to keep users on the current page.', 'constant-contact-forms' ),
			'sanitization_cb' => 'constant_contact_clean_url',
		) );

		$options_metabox->add_field( array(
			'name'        => __( 'No page refresh', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'do_ajax',
			'type'        => 'checkbox',
			'description' => __( 'Enable form submission without a page refresh. This option overrides the Redirect URL choice above.', 'constant-contact-forms' ),
		) );

		if ( constant_contact()->settings->has_recaptcha() ) {
			$options_metabox->add_field( array(
				'name'        => __( 'Disable Google reCAPTCHA for this form?', 'constant-contact-forms' ),
				'id'          => $this->prefix . 'disable_recaptcha',
				'type'        => 'checkbox',
				'description' => __( "Checking will disable Google's reCAPTCHA output for this form.", 'constant-contact-forms' ),
			) );
		}

		$options_metabox->add_field( array(
			'name'  => esc_html__( 'Spam notice', 'constant-contact-forms' ),
			'type'  => 'title',
			'id'    => 'spam_notice_title',
			'after' => '<hr/>',
		) );

		$options_metabox->add_field( array(
			'name'            => __( 'Spam Error Message', 'constant-contact-forms' ),
			'id'              => $this->prefix . 'spam_error',
			'type'            => 'text',
			'description'     => esc_html__( 'Set the spam error message displayed for this form.', 'constant-contact-forms' ),
		) );

		if ( constant_contact()->api->is_connected() ) {
			$this->show_optin_connected_fields( $options_metabox );
		}
	}

	/**
	 * Metabox for user to set custom CSS for a form.
	 *
	 * @since 1.4.0
	 */
	public function custom_form_css_metabox() {
		$custom_css_metabox = new_cmb2_box( array(
			'id'           => 'ctct_1_custom_form_css_metabox',
			'title'        => __( 'Form Design', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'side',
			'priority'     => 'low',
		) );

		$custom_css_metabox->add_field( array(
			'name'        => __( 'Background Color', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'form_background_color',
			'type'        => 'colorpicker',
			'description' => esc_html__(
				'Applies to the whole form.',
				'constant-contact-forms'
			),
		) );

		$custom_css_metabox->add_field( array(
			'name' => esc_html__( 'Form Fonts', 'constant-contact-forms' ),
			'type' => 'title',
			'id'   => 'form-description-title',
		) );

		$custom_css_metabox->add_field( array(
			'name'             => __( 'Font Size', 'constant-contact-forms' ),
			'id'               => $this->prefix . 'form_description_font_size',
			'type'             => 'select',
			'show_option_none' => 'Default',
			'options_cb'       => 'constant_contact_get_font_dropdown_sizes',
			'description'      => esc_html__(
				'Only applies to the form description.',
				'constant-contact-forms'
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'        => __( 'Font Color', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'form_description_color',
			'type'        => 'colorpicker',
			'description' => esc_html__(
				'Applies to the form description, input labels, and disclosure text.',
				'constant-contact-forms'
			),
		) );

		$custom_css_metabox->add_field( array(
			'name' => esc_html__( 'Form Submit Button', 'constant-contact-forms' ),
			'type' => 'title',
			'id'   => 'form-submit-button-title',
		) );

		$custom_css_metabox->add_field( array(
			'name'             => __( 'Font Size', 'constant-contact-forms' ),
			'id'               => $this->prefix . 'form_submit_button_font_size',
			'type'             => 'select',
			'show_option_none' => 'Default',
			'options_cb'       => 'constant_contact_get_font_dropdown_sizes',
		) );

		$custom_css_metabox->add_field( array(
			'name'        => __( 'Font Color', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'form_submit_button_text_color',
			'type'        => 'colorpicker',
			'description' => esc_html__(
				'Choose a color for the submit button text.',
				'constant-contact-forms'
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'        => __( 'Background Color', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'form_submit_button_background_color',
			'type'        => 'colorpicker',
			'description' => esc_html__(
				'Choose a color for the submit button background.',
				'constant-contact-forms'
			),
		) );
	}

	/**
	 * Metabox for user to set custom CSS for a form.
	 *
	 * @since 1.4.0
	 */
	public function custom_input_css_metabox() {
		$custom_css_metabox = new_cmb2_box( array(
			'id'           => 'ctct_1_custom_input_css_metabox',
			'title'        => __( 'Input Design', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'side',
			'priority'     => 'low',
		) );

		$custom_css_metabox->add_field( array(
			'name'        => esc_html__( 'Form Padding', 'constant-contact-forms' ),
			'type'        => 'title',
			'id'          => 'form-padding-title',
			'description' => esc_html__(
				'Enter padding values in number of pixels. Padding will be applied to four sides of the form.',
				'constant-contact-form' ),
		) );

		$custom_css_metabox->add_field( array(
			'name'       => __( 'Top', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'form_padding_top',
			'type'       => 'text_small',
			'show_names' => true,
			'attributes' => array(
				'type' => 'number',
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'       => __( 'Right', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'form_padding_right',
			'type'       => 'text_small',
			'show_names' => true,
			'attributes' => array(
				'type' => 'number',
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'       => __( 'Bottom', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'form_padding_bottom',
			'type'       => 'text_small',
			'show_names' => true,
			'attributes' => array(
				'type' => 'number',
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'       => __( 'Left', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'form_padding_left',
			'type'       => 'text_small',
			'show_names' => true,
			'attributes' => array(
				'type' => 'number',
			),
		) );

		$custom_css_metabox->add_field( array(
			'name'        => __( 'Custom Classes', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'input_custom_classes',
			'type'        => 'text',
			'description' => esc_html__(
				'Set custom CSS class(es) for inputs. Separate multiple classes with spaces.',
				'constant-contact-forms'
			),
		) );

		$custom_css_metabox->add_field( [
			'name'             => __( 'Label Placement', 'constant-contact-forms' ),
			'id'               => $this->prefix . 'form_label_placement',
			'type'             => 'select',
			'show_option_none' => esc_html__( 'Global', 'constant-contact-forms' ),
			'options'          => [
				'top'    => esc_html__( 'Top', 'constant-contact-forms' ),
				'left'   => esc_html__( 'Left', 'constant-contact-forms' ),
				'bottom' => esc_html__( 'Bottom', 'constant-contact-forms' ),
				'right'  => esc_html__( 'Right', 'constant-contact-forms' ),
				'hidden' => esc_html__( 'Hidden', 'constant-contact-forms' ),
			],
			'description'      => esc_html__(
				'Set the position for labels for inputs.',
				'constant-contact-forms'
			),
		] );
	}

	/**
	 * Helper method to show our connected optin fields.
	 *
	 * @since 1.0.0
	 *
	 * @param object $options_metabox CMB2 options metabox object.
	 */
	public function show_optin_connected_fields( $options_metabox ) {

		$overall_description = sprintf(
			'<hr/><p>%s %s</p>',
			esc_html__(
				'Enabling this option will require users to check a box to be added to your list.',
				'constant-contact-forms'
			),
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://knowledgebase.constantcontact.com/articles/KnowledgeBase/18260-WordPress-Constant-Contact-Forms-Options',
				esc_html__( 'Learn more', 'constant-contact-forms' )
			)
		);

		$options_metabox->add_field( array(
			'name'  => esc_html__( 'Email opt-in', 'constant-contact-forms' ),
			'type'  => 'title',
			'id'    => 'email-optin-title',
			'after' => $overall_description,
		) );

		// Show our show/hide checkbox field.
		$this->show_enable_show_checkbox_field( $options_metabox );

		// Show our affirmation textbox field.
		$this->show_affirmation_field( $options_metabox );
	}

	/**
	 * Helper method to show our non connected optin fields.
	 *
	 * @since 1.0.0
	 *
	 * @param object $options_metabox CMB2 options metabox object.
	 */
	public function show_optin_not_connected_fields( $options_metabox ) {

		$options_metabox->add_field( array(
			'name'        => __( 'Enable email subscriber opt-in', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'opt_in_not_connected',
			'description' => __( 'Adds an opt-in to the bottom of your form.', 'constant-contact-forms' ),
			'type'        => 'checkbox',
			'attributes'  => array(
				'disabled' => 'disabled',
			),
		) );

		// Show our affirmation textbox field.
		$this->show_affirmation_field( $options_metabox );
	}

	/**
	 * Helper method to show our show/hide checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @param object $options_metabox CMB2 options metabox object.
	 */
	public function show_enable_show_checkbox_field( $options_metabox ) {

		$description = esc_html__( 'Add a checkbox so subscribers can opt-in to your email list.', 'constant-contact-forms' );
		$description .= '<br>';
		$description .= esc_html__( '(For use with Contact Us form)', 'constant-contact-forms' );

		$options_metabox->add_field( array(
			'name'        => __( 'Opt-in checkbox', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'opt_in',
			'description' => $description,
			'type'        => 'checkbox',
		) );
	}

	/**
	 * Helper method to show our affirmation textarea field.
	 *
	 * @since 1.0.0
	 *
	 * @param object $options_metabox CMB2 options metabox object.
	 */
	public function show_affirmation_field( $options_metabox ) {

		// Get our site name, and if we don't have it, then use a placeholder.
		$business_name = get_bloginfo( 'name' );
		$business_name ? ( $business_name ) : __( 'Your Business Name', 'constant-contact-forms' );

		$options_metabox->add_field( array(
			'name'    => __( 'Opt-in Affirmation', 'constant-contact-forms' ),
			'id'      => $this->prefix . 'opt_in_instructions',
			'type'    => 'textarea_small',
			// translators: placeholder has a business name from Constant Contact.
			'default' => sprintf( __( 'Example: Yes, I would like to receive emails from %s. (You can unsubscribe anytime)', 'constant-contact-forms' ), $business_name ),
		) );
	}

	/**
	 * Fields builder CMB2 metabox.
	 *
	 * @since 1.0.0
	 */
	public function fields_metabox() {

		/**
		 * Initiate the $fields_metabox.
		 */
		$fields_metabox = new_cmb2_box( array(
			'id'           => 'ctct_2_fields_metabox',
			'title'        => __( 'Form Fields', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'normal',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		// Custom CMB2 fields.
		$fields_metabox->add_field( array(
			'name'        => __( 'Add Fields', 'constant-contact-forms' ),
			/**
			 * No birthdays or anniversarys in CC API V2, keeping this for later.
			 * "You can also collect birthday and anniversary dates to use with Constant Contact autoresponders! "
			 * @since 1.0.2
			 */
			'description' => __( 'Create a field for each piece of information you want to collect. Good basics include email address, first name, and last name.', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'title',
			'type'        => 'title',
		) );

		// Form builder repeater.
		$custom_group = $fields_metabox->add_field( array(
			'id'         => 'custom_fields_group',
			'type'       => 'group',
			'repeatable' => true,
			'options'    => array(
				'group_title'   => __( 'Field {#}', 'constant-contact-forms' ),
				'add_button'    => __( 'Add Another Field', 'constant-contact-forms' ),
				'remove_button' => __( 'Remove Field', 'constant-contact-forms' ),
				'sortable'      => true,
			),
		) );

		/**
		 * The default placeholder text to use for fields without a placeholder.
		 *
		 * @since 1.2.0
		 *
		 * @param string $default_placeholder The placeholder text.
		 */
		$default_placeholder = apply_filters( 'constant_contact_default_placeholder', __( 'A brief description of this field (optional)', 'constant-contact-forms' ) );

		// Define field configuration for options and placeholders.
		$default_fields = array(
			'email' => array(
				'option'      => __( 'Email (required)', 'constant-contact-forms' ),
				'placeholder' => __( 'c.contact@example.com', 'constant-contact-forms' ),
			),
			'first_name' => array(
				'option'      => __( 'First Name', 'constant-contact-forms' ),
				'placeholder' => __( 'John', 'constant-contact-forms' ),
			),
			'last_name' => array(
				'option'      => __( 'Last Name', 'constant-contact-forms' ),
				'placeholder' => __( 'Smith', 'constant-contact-forms' ),
			),
			'phone_number' => array(
				'option'      => __( 'Phone Number', 'constant-contact-forms' ),
				'placeholder' => __( '(555) 272-3342', 'constant-contact-forms' ),
			),
			'address' => array(
				'option'      => __( 'Address', 'constant-contact-forms' ),
				'placeholder' => __( '4115 S. Main Rd.', 'constant-contact-forms' ),
			),
			'job_title' => array(
				'option'      => __( 'Job Title', 'constant-contact-forms' ),
				'placeholder' => __( 'Project Manager', 'constant-contact-forms' ),
			),
			'company' => array(
				'option'      => __( 'Company', 'constant-contact-forms' ),
				'placeholder' => __( 'Acme Manufacturing', 'constant-contact-forms' ),
			),
			'website' => array(
				'option'      => __( 'Website', 'constant-contact-forms' ),
				'placeholder' => __( 'http://www.example.com', 'constant-contact-form' ),
			),
			/**
			 * V2 of the CC API doesn't support these fields. Hopefully this will get sorted out.
			 * 'birthday' => array(
			 *     'option' => __( 'Birthday', 'constant-contact-forms' ),
			 *     'placeholder' => 'M/D/Y',
			 * ),
			 * 'anniversary'      => array(
			 *     'option' => __( 'Anniversary', 'constant-contact-forms' ),
			 *     'placeholder' => 'M/D/Y',
			 *     ),
			 * @since 1.0.2
			 */
			'custom' => array(
				'option'      => __( 'Custom Text Field', 'constant-contact-forms' ),
				'placeholder' => __( 'A custom text field', 'constant-contact-forms' ),
			),
			'custom_text_area' => array(
				'option'      => __( 'Custom Text Area', 'constant-contact-forms' ),
				'placeholder' => __( 'A large custom text field', 'constant-contact-forms' ),
			),
		);

		/**
		 * Filters the Constant Contact field types to display as an option.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value Array of field types.
		 */
		$filtered_options = apply_filters( 'constant_contact_field_types', wp_list_pluck( $default_fields, 'option' ) );

		/**
		 * Filter the field placeholders.
		 *
		 * @since 1.2.0
		 *
		 * @param array $default_fields The field placeholders to use for field description.
		 */
		$filtered_placeholders            = apply_filters(
			'constant_contact_field_placeholders',
			wp_list_pluck( $default_fields, 'placeholder' )
		);
		$filtered_placeholders['default'] = $default_placeholder;

		// Go ahead and enqueue with our placeholder text.
		$this->plugin->admin->scripts( array(
			'ctct_form',
			'ctct_admin_placeholders',
			$filtered_placeholders,
		) );

		// Choose which field.
		$fields_metabox->add_group_field( $custom_group, array(
			'name'             => __( 'Select a Field', 'constant-contact-forms' ),
			'id'               => $this->prefix . 'map_select',
			'type'             => 'select',
			'show_option_none' => false,
			'default'          => 'email',
			'row_classes'      => 'map',
			'options'          => $filtered_options,
		) );

		// Add a field label.
		$fields_metabox->add_group_field( $custom_group, array(
			'name'    => __( 'Field Label', 'constant-contact-forms' ),
			'id'      => $this->prefix . 'field_label',
			'type'    => 'text',
			'default' => '',
		) );

		// Add our field description.
		$fields_metabox->add_group_field( $custom_group, array(
			'name'       => __( 'Field Description', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'field_desc',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'Ex: Enter email address', 'constant-contact-forms' ),
			),
		) );

		// Allow toggling of required fields.
		$fields_metabox->add_group_field( $custom_group, array(
			'name'        => __( 'Required', 'constant-contact-forms' ),
			'id'          => $this->prefix . 'required_field',
			'type'        => 'checkbox',
			'row_classes' => 'required',
		) );

	}

	/**
	 * Show a metabox rendering our shortcode.
	 *
	 * @since 1.1.0
	 */
	public function generated_shortcode() {
		$generated = new_cmb2_box( array(
			'id'           => 'ctct_2_generated_metabox',
			'title'        => __( 'Shortcode', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		$generated->add_field( array(
			'name'       => __( 'Shortcode to use', 'constant-contact-forms' ),
			'id'         => $this->prefix . 'generated_shortcode',
			'type'       => 'text_medium',
			'desc'       => __( 'Shortcode to embed - <em><small>You can copy and paste this in a post to display your form.</small></em>', 'constant-contact-forms' ),
			'default'    => ( $generated->object_id > 0 ) ? '[ctct form="' . $generated->object_id . '" show_title="false"]' : '',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );
	}

	/**
	 * Add a metabox for customizing destination email for a given form.
	 *
	 * @since 1.4.0
	 */
	public function email_settings() {

		$email_settings = new_cmb2_box( array(
			'id'           => 'email_settings',
			'title'        => esc_html__( 'Email settings', 'constant-contact-forms' ),
			'object_types' => array( 'ctct_forms' ),
			'context'      => 'side',
			'priority'     => 'low',
		) );

		$email_settings->add_field( array(
			'name' => esc_html__( 'Email destination', 'constant-contact-forms' ),
			'desc' => esc_html__( 'Who should receive email notifications for this form. Separate multiple emails by a comma. Leave blank to default to admin email.', 'constant-contact-forms' ),
			'id'   => $this->prefix . 'email_settings',
			'type' => 'text_medium',
		) );

		$email_settings->add_field( array(
			'name' => esc_html__( 'Disable email notifications for this form?', 'constant-contact-forms' ),
			'desc' => esc_html__( 'Check this option to disable emails for this Constant Contact Forms form.', 'constant-contact-forms' ),
			'id'   => $this->prefix . 'disable_emails_for_form',
			'type' => 'checkbox',
		) );
	}

	/**
	 * Render the metabox for resetting style fields.
	 *
	 * @since 1.5.0
	 */
	public function add_css_reset_metabox() {
		$reset_css_metabox = new_cmb2_box(
			array(
				'id'           => 'ctct_3_reset_css_metabox',
				'title'        => __( 'Reset Styles', 'constant-contact-forms' ),
				'object_types' => array( 'ctct_forms' ),
				'context'      => 'side',
				'priority'     => 'low',
			)
		);

		$reset_css_metabox->add_field(
			array(
				'id'          => $this->prefix . 'reset_styles',
				'type'        => 'reset_css_button',
				'title'       => esc_html__( 'Reset', 'constant-contact-forms' ),
				'description' => esc_html__(
					'Reset the styles for this Form.',
					'constant-contact-forms'
				),
			)
		);
	}

	/**
	 * Render the Reset Style button.
	 *
	 * @since 1.5.0
	 * @param object $field The CMB2 field object.
	 */
	public function render_reset_css_button( $field ) {
?>
<button type="button" id="ctct-reset-css" class="button">
	<?php esc_html_e( 'Reset', 'constant-contact-forms' ); ?>
</button>
<p>
<em>
	<?php echo esc_html( $field->args['description'] ); ?>
</em>
</p>
<?php
	}
}
