<?php
/**
 * ConstantContact_Settings
 *
 * @package ConstantContact_Settings
 * @author Pluginize
 * @since 1.0.0
 */

/**
 * ConstantContact_Settings class
 */
class ConstantContact_Settings {

	/**
	 * Option key, and option page slug
	 *
	 * @var string
	 * @since  1.0.0
	 */
	private $key = 'ctct_options_settings';

	/**
	 * Settings page metabox id
	 *
	 * @var string
	 * @since  1.0.0
	 */
	private $metabox_id = 'ctct_option_metabox_settings';

	/**
	 * Settings Page title
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $title = '';

	/**
	 * Settings Page hook
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $options_page = '';

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
	 * @param object $plugin parent plugin instance.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

		// Override CMB's getter.
		add_filter( 'cmb2_override_option_get_' . $this->key, array( $this, 'get_override' ), 10, 2 );
		// Override CMB's setter.
		add_filter( 'cmb2_override_option_save_' . $this->key, array( $this, 'update_override' ), 10, 2 );

		add_action( 'cmb2_init', array( $this, 'add_optin_to_forms' ) );
		add_filter( 'preprocess_comment', array( $this, 'process_optin_comment_form' ) );
		add_filter( 'authenticate', array( $this, 'process_optin_login_form' ), 10, 3 );

	}

	/**
	 * Register our setting to WP
	 *
	 * @since 1.0.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 *
	 * @since 1.0.0
	 */
	public function add_options_page() {

		$this->options_page = add_submenu_page(
			'edit.php?post_type=ctct_forms',
			__( 'Settings', 'constantcontact' ),
			__( 'Settings', 'constantcontact' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);

		// Include CMB CSS in the head to avoid FOUC.
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since 1.0.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo esc_attr( $this->key ); ?>">
			<h2><?php esc_attr_e( ' Settings', 'constantcontact' ); ?></h2>
			<?php
			if ( function_exists( 'cmb2_metabox_form' ) ) {
				cmb2_metabox_form( $this->metabox_id, $this->key );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 *
	 * @since  1.0.0
	 */
	function add_options_page_metabox() {

		// Hook in our save notices.
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		$cmb = new_cmb2_box( array(
			'id'		 => $this->metabox_id,
			'hookup'	 => false,
			'cmb_styles' => false,
			'show_on'	=> array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$option_options = array(
			'comment_form' => __( 'Comment Form', 'constantcontact' ),
			'login_form'   => __( 'Login Form', 'constantcontact' ),
		);

		if ( get_option( 'users_can_register' ) ) {
			$option_options['reg_form'] = __( 'Registration Form', 'constantcontact' );
		}

		// Get our lists.
		$lists = constant_contact()->builder->get_lists();

		if ( $lists && is_array( $lists ) ) {

			unset( $lists['new'] );

			// Set our CMB2 fields.
			$cmb->add_field( array(
				'name' 	=> __( 'Opt In', 'constantcontact' ),
				'desc' 	=> __( 'Add opt in checkbox to selected forms.', 'constantcontact' ),
				'id'   	=> '_ctct_optin_forms',
				'type'	=> 'multicheck',
				'options' => $option_options,
			) );

			$cmb->add_field( array(
				'name' 	=> __( 'Opt In List', 'constantcontact' ),
				'desc' 	=> __( 'Choose list to add opt in subsciptions.', 'constantcontact' ),
				'id'   	=> '_ctct_optin_list',
				'type'	=> 'select',
				'show_option_none' => true,
				'default'		  => 'none',
				'options'		  => $lists,
			) );
		}

		$cmb->add_field( array(
			'name' 	          => __( 'API Client Key', 'constantcontact' ),
			'id'   	          => '_ctct_api_key',
			'type'	          => 'text',
			'desc'            => __( 'Please go <a target="_blank" href="https://constantcontact.mashery.com/page">here</a> and register a new app and API keys.', 'constantcontact' ),
			'sanitization_cb' => array( $this, 'save_api_keys' ),
			'escape_cb'       => array( $this, 'mask_api_keys' ),
		) );

		$cmb->add_field( array(
			'name' 	          => __( 'API Secret', 'constantcontact' ),
			'id'   	          => '_ctct_api_secret',
			'type'	          => 'text',
			'sanitization_cb' => array( $this, 'save_api_keys' ),
			'escape_cb'       => array( $this, 'mask_api_keys' ),
		) );

		$cmb->add_field( array(
			'name' 	          => __( '(temporary) Middlware Address', 'constantcontact' ),
			'id'   	          => '_ctct_auth_server_link',
			'type'	          => 'text',
		) );

	}

	/**
	 * Add selected optin to forms.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_optin_to_forms() {

		if ( ! constant_contact()->builder->get_lists() ) {
			return;
		}

		$optin_selected = ctct_get_settings_option( '_ctct_optin_forms' );

		if ( ! is_array( $optin_selected ) ) {
			return;
		}

		foreach ( $optin_selected as $selected ) {
			switch ( $selected ) {
				case 'login_form':
					add_action( 'login_form', array( $this, 'optin_form_field' ) );
				break;
				case 'comment_form':
					add_action( 'comment_form_after_fields', array( $this, 'optin_form_field' ) );
				break;
				case 'reg_form':
					add_action( 'register_form', array( $this, 'optin_form_field' ) );
					add_action( 'signup_extra_fields', array( $this, 'optin_form_field' ) );
				break;
			}
		}
	}

	/**
	 * Opt in field checkbox
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function optin_form_field() {

		$label = ctct_get_settings_option( '_ctct_optin_label' ) ? ctct_get_settings_option( '_ctct_optin_label' ) : __( 'Sign up to our newsletter.', 'constantcontact' );
	?>
	    <p style="padding: 0 0 1em 0;">
	        <label for="ctct_optin">
	        <input type="checkbox" value="<?php echo esc_attr( ctct_get_settings_option( '_ctct_optin_list' ) ); ?>" class="checkbox" id="ctct_optin" name="ctct_optin_list">
			<?php echo sanitize_text_field( $label ); ?>
			</label>
			<?php echo wp_nonce_field( 'ct_ct_add_to_optin', 'ct_ct_optin' ); ?>
	    </p>
	<?php

	}

	/**
	 * Sends contact to CTCT if optin checked
	 *
	 * @since  1.0.0
	 * @param  array $comment_data comment form data.
	 * @return array comment form data
	 */
	public function process_optin_comment_form( $comment_data ) {

		// Sanity check
		if ( ! isset( $_POST['ctct_optin_list'] ) ) {
			return $comment_data;
		}

		// nonce sanity check
		if ( ! isset( $_POST['ct_ct_optin'] ) ) {
			return $comment_data;
		}

		// Check our nonce
		if ( ! wp_verify_nonce( $_POST['ct_ct_optin'], 'ct_ct_add_to_optin' ) ) {
			return $comment_data;
		}

		// finally, if we have our data, then add it to the api
		if ( isset( $comment_data['comment_author'] ) && isset( $comment_data['comment_author'] ) ) {

			$args = array(
				'email' => sanitize_email( $comment_data['comment_author_email'] ),
				'list' => sanitize_text_field( $_POST['ctct_optin_list'] ),
				'first_name' => sanitize_text_field( $comment_data['comment_author'] ),
				'last_name' => '',
			);

			constantcontact_api()->add_contact( $args );
		}

		return $comment_data;
	}

	/**
	 * Sends contact to CTCT if optin checked
	 *
	 * @since  1.0.0
	 * @param  array  $user
	 * @param  string $username login name.
	 * @param  string $password user password.
	 * @return object  CTCT return API for contact
	 */
	public function process_optin_login_form( $user, $username, $password ) {

		// Sanity check
		if ( ! isset( $_POST['ctct_optin_list'] ) ) {
			return $user;
		}

		// nonce sanity check
		if ( ! isset( $_POST['ct_ct_optin'] ) ) {
			return $user;
		}

		// Check our nonce
		if ( ! wp_verify_nonce( $_POST['ct_ct_optin'], 'ct_ct_add_to_optin' ) ) {
			return $user;
		}

		// Check username
		if ( empty( $username ) ) {
			return $user;
		}

		// Get user
		$user_data = get_user_by( 'login', $username );

		// Get email
		if ( $user_data && isset( $user_data->data ) && isset( $user_data->data->user_email ) ) {
			$email = sanitize_email( $user_data->data->user_email );
		} else {
			$email = '';
		}

		// Get name
		if ( $user_data && isset( $user_data->data ) && isset( $user_data->data->display_name ) ) {
			$name = sanitiize_text_field( $user_data->data->display_name );
		} else {
			$name = '';
		}

		// IF we have one or the other, try it
		if ( $name || $email ) {
			$args = array(
				'email' => $email,
				'list' => sanitize_text_field( wp_unslash( $_POST['ctct_optin_list'] ) ),
				'first_name' => $name,
				'last_name' => '',
			);

			constantcontact_api()->add_contact( $args );
		}

		return $user;
	}

	/**
	 * Register settings notices for display
	 *
	 * @since  1.0.0
	 * @param  int   $object_id Option key
	 * @param  array $updated   Array of updated fields
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'constantcontact' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/**
	 * Replaces get_option with get_site_option
	 *
	 * @since  1.0.0
	 * @return mixed site option
	 */
	public function get_override( $test, $default = false ) {
		return get_site_option( $this->key, $default );
	}

	/**
	 * Replaces update_option with update_site_option
	 *
	 * @since  1.0.0
	 * @return mixed site option
	 */
	public function update_override( $test, $option_value ) {
		return update_site_option( $this->key, $option_value );
	}

	/**
	 * Encrpyts our api keys on server
	 *
	 * @since  1.0.0
	 */
	public function save_api_keys( $value, $field_args, $field ) {

		// If we don't have an ID, return orig value
		if ( ! isset( $field_args['id'] ) ) {
			return $value;
		}

		// If the id is not an api key, return
		if ( ! ( ( '_ctct_api_key' === $field_args['id'] ) || ( '_ctct_api_secret' === $field_args['id'] ) ) ) {
			return $value;
		}

		// if we are looking at are masked password, we want to bypass saving
		if ( $this->get_mask() === $value ) {
			$value = constant_contact()->connect->e_get( esc_attr( $field_args['id'] ), true );
		}

		// Encrypt and return
		return constant_contact()->connect->e_set( esc_attr( $field_args['id'] ), sanitize_text_field( $value ) );
	}

	/**
	 * Hides our api keys from display
	 *
	 * @since  1.0.0
	 */
	public function mask_api_keys( $value, $field_args, $field ) {

		// If we don't have an ID, return orig value
		if ( ! isset( $field_args['id'] ) ) {
			return $value;
		}

		// If the id is not an api key, return
		if ( ! ( ( '_ctct_api_key' === $field_args['id'] ) || ( '_ctct_api_secret' === $field_args['id'] ) ) ) {
			return $value;
		}

		// If they hvaen't entered anything, don't show mask
		if ( empty( $value ) ) {
			return '';
		}

		return $this->get_mask();
	}

	/**
	 * Returns text for field masking
	 *
	 * @since  1.0.0
	 * @return string bunch of ***
	 */
	public function get_mask() {
		return '**********';
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  1.0.0
	 * @param  string $field Field to retrieve.
	 * @return mixed Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			if ( isset( $this->{$field} ) ) {
				return $this->{$field};
			} else {
				return null;
			}
		}

		throw new Exception( 'Invalid property: ' . $field );
	}
}


/**
 * Wrapper function around cmb2_get_option
 *
 * @since  1.0.0
 * @param  string $key Options array key.
 * @return mixed Option value
 */
function ctct_get_settings_option( $key = '' ) {
	return cmb2_get_option( constant_contact()->settings->key, $key );
}
