<?php
/**
 * @package ConstantContact
 * @subpackage Notifications_Content
 * @author Pluginize
 * @since 1.0.0
 */

/**
 * Holds notification content for easy manipulation
 */
class ConstantContact_Notification_Content {

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
	 * Display our notification content for our activation message
	 *
	 * @since   1.0.0
	 */
	public static function activation() {

		// Start our output buffer
		ob_start();

		?>
		<p class="ctct-notice-intro">
		<?php
			printf(
				esc_attr__( 'To take full advantage of the %s plugin, we recommend having an active Constant Contact account.', 'constantcontact' ),
				'<strong>' . esc_attr__( 'Constant Contact Forms' ) . '</strong>'
			);
		?>
		</p>
		<p>
			<a href="<?php echo esc_url_raw( constant_contact()->api->get_connect_link() ); ?>" class="ctct-notice-button button-primary">
				<?php esc_attr_e( 'Connect your account', 'constantcontact' ); ?>
			</a>
			<a href="<?php echo esc_url_raw( constant_contact()->api->get_signup_link() ); ?>" class="ctct-notice-button button-secondary">
				<?php esc_attr_e( 'Try Us Free', 'constantcontact' ); ?>
			</a>
		</p>
		<?php

		// Get our output and send it back
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * Notification content for our 'too many lists' error
	 *
	 * @since   1.0.0
	 * @return  string  notification text
	 */
	public static function too_many_lists() {
		return __( 'You currently have a large number of lists in your Constant Contact account. You may experience some issues with syncing them.', 'constantcontact' );
	}

	/**
	 * Sample update notification for updating to 1.0.1
	 *
	 * @since   1.0.0
	 * @return  string    notification text
	 */
	public static function v1_0_1() {

		// This is an example of outputting the text for a notification.
		// The @codingStand.. is to suppress PHPCS warnings about commented code
		// @codingStandardsIgnoreLine
		// return __( 'Welcome to v1.0.1 of Constant Contact.', 'constantcontact' );
	}
}

