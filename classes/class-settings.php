<?php
/**
 * CMB2 Network Settings
 * @version 1.0.0
 */
class ConstantContact_Settings {

	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'ctct_options_settings';

	/**
 	 * Settings page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'ctct_option_metabox_settings';

	/**
	 * Settings Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Settings Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Holds an instance of the project
	 *
	 * @Myprefix_Network_Admin
	 **/
	private static $instance = null;

	/**
	 * Constructor
	 * @since 1.0.0
	 */
	private function __construct() {
	}

	/**
	 * Get the running object
	 *
	 * @return Myprefix_Network_Admin
	 **/
	public static function get_instance() {
		if( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Initiate our hooks
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

		// Override CMB's getter
		add_filter( 'cmb2_override_option_get_'. $this->key, array( $this, 'get_override' ), 10, 2 );
		// Override CMB's setter
		add_filter( 'cmb2_override_option_save_'. $this->key, array( $this, 'update_override' ), 10, 2 );
	}

	/**
	 * Register our setting to WP
	 * @since  1.0.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 1.0.0
	 */
	public function add_options_page() {

		$this->options_page = add_submenu_page(
			'edit.php?post_type=ctct_forms',
			__( 'Settings', constant_contact()->text_domain ),
			__( 'Settings', constant_contact()->text_domain ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);

		// add_action( "admin_head-{$this->options_page}", array( $this, 'enqueue_js' ) );
		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  1.0.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">
			<h2><?php esc_attr_e( ' Settings', constant_contact()->text_domain ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  1.0.0
	 */
	function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		$cmb = new_cmb2_box( array(
			'id'		 => $this->metabox_id,
			'hookup'	 => false,
			'cmb_styles' => false,
			'show_on'	=> array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		$option_options = array(
			'comment_form' => __( 'Comment Form', constant_contact()->text_domain ),
			'login_form' => __( 'Login Form', constant_contact()->text_domain ),
		);

		if ( get_option( 'users_can_register' ) ) {
			$option_options['reg_form'] = __( 'Registration Form', constant_contact()->text_domain );
		}

		// Set our CMB2 fields
		$cmb->add_field( array(
			'name' 	=> __( 'Opt In', constant_contact()->text_domain ),
			'desc' 	=> __( 'Add opt in checkbox to selected forms.', constant_contact()->text_domain ),
			'id'   	=> '_ctct_optin_forms',
			'type'	=> 'multicheck',
			'options' => $option_options,
		) );

		$cmb->add_field( array(
			'name' 	=> __( 'Opt In List', constant_contact()->text_domain ),
			'desc' 	=> __( 'Choose list to add opt in subsciptions.', constant_contact()->text_domain ),
			'id'   	=> '_ctct_optin_list',
			'type'	=> 'select',
			'show_option_none' => true,
			'default'		  => 'none',
			'options'		  => array(
				'standard' 	=> __( 'Option One', constant_contact()->text_domain ),
				'custom'   	=> __( 'Option Two', constant_contact()->text_domain ),
				'three'	 	=> __( 'Option Three', constant_contact()->text_domain ),
			),
		) );

		$cmb->add_field( array(
			'name' 	=> __( 'API key', constant_contact()->text_domain ),
			'id'   	=> '_ctct_api_key',
			'type'	=> 'text',
		) );
		$cmb->add_field( array(
			'name' 	=> __( 'API Secret', constant_contact()->text_domain ),
			'id'   	=> '_ctct_api_secret',
			'type'	=> 'text',
		) );

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

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', constant_contact()->text_domain ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/**
	 * Replaces get_option with get_site_option
	 * @since  1.0.0
	 */
	public function get_override( $test, $default = false ) {
		return get_site_option( $this->key, $default );
	}

	/**
	 * Replaces update_option with update_site_option
	 * @since  1.0.0
	 */
	public function update_override( $test, $option_value ) {
		return update_site_option( $this->key, $option_value );
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  1.0.0
	 * @param  string  $field Field to retrieve
	 * @return mixed		  Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Helper function to get/return the Myprefix_Network_Admin object
 * @since  1.0.0
 * @return Myprefix_Network_Admin object
 */
function ctct_settings_admin() {
	return ConstantContact_Settings::get_instance();
}

/**
 * Wrapper function around cmb2_get_option
 * @since  1.0.0
 * @param  string  $key Options array key
 * @return mixed		Option value
 */
function ctct_get_settings_option( $key = '' ) {
	return cmb2_get_option( myprefix_admin()->key, $key );
}

// Get it started
ctct_settings_admin();
