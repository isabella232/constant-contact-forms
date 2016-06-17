<?php
/**
 * ConstantContact_Lists class
 *
 * @package ConstantContactLists
 * @subpackage ConstantContact
 * @author Pluginize
 * @since 1.0.0
 */

require_once constant_contact()->dir() . 'vendor/constantcontact/constantcontact/constantcontact/src/Ctct/autoload.php';

use Ctct\ConstantContact;
use Ctct\Exceptions\CtctException;

/**
 * Class ConstantContact_Lists
 */
class ConstantContact_Lists {

	/**
	 * Holds an instance of the object.
	 *
	 * @since 1.0.0
	 * @var ConstantContact_Lists
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
	}

	/**
	 * Returns the running object
	 *
	 * @since 1.0.0
	 * @return ConstantContact_Lists
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new ConstantContact_Lists();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'cmb2_init', array( $this, 'sync_lists' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_lists_metabox' ) );

		add_action( 'save_post_ctct_lists', array( $this, 'save_list' ) );
		add_action( 'save_post_ctct_lists', array( $this, 'update_list' ) );
		add_action( 'wp_trash_post', array( $this, 'delete_list' ) );

	}

	/**
	 * CMB2 metabox for list data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_lists_metabox() {

		$cmb = new_cmb2_box( array(
			'id' => 'ctct_list_metabox',
			'title' => __( 'List Meta', constant_contact()->text_domain ),
			'object_types'  => array( 'ctct_lists' ),
			'context'	   => 'normal',
			'priority' => 'high',
			'show_names'	=> true,
		) );

		$post_meta = get_post_meta( $cmb->object_id(), '_ctct_list_id', true );

		$cmb->add_field( array(
			'name' 	=> __( 'ID', constant_contact()->text_domain ),
			'desc' 	=> $post_meta,
			'id'   	=> '_ctct_list_meta',
			'type'	=> 'title',
		) );

	}

	/**
	 * Syncs list cpt with lists on CTCT
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sync_lists() {
		global $pagenow;

		if ( ! $token = constantcontact_api()->get_api_token() ) { return; }

		if ( in_array( $pagenow, array( 'edit.php' ), true ) && isset( $_GET['post_type'] ) && 'ctct_lists' === $_GET['post_type'] ) {

			$args = array(
				'post_type'	=> 'ctct_lists',
			);
			$my_query = new WP_Query( $args );

			foreach ( $my_query->posts as $post ) {
				wp_delete_post( $post->ID, true );
			}

			if ( $lists = constantcontact_api()->get_lists() ) {

				foreach ( $lists as $list ) {

						$new_post = array(
							  'post_title'	=> wp_strip_all_tags( $list->name ),
							  'post_status'   => 'publish',
							  'post_type' => 'ctct_lists',
						);
						$post = wp_insert_post( $new_post );
						update_post_meta( $post, '_ctct_list_id', $list->id );

				}

				/**
				 * Hook when a ctct list is updated.
				 *
				 * @since 1.0.0
				 * @param array $list ctct returned list data
				 */
				do_action( 'ctct_sync_lists', $lists );
			}
		}
	}

	/**
	 * Saves list cpt and sends add list request to CTCT
	 *
	 * @since 1.0.0
	 * @param  integer $post_id current post id.
	 * @return void
	 */
	public function save_list( $post_id ) {
		global $pagenow;

		if ( 'post-new.php' === $pagenow ) {

			$ctct_list = get_post( $post_id );

			if ( isset( $ctct_list ) && $ctct_list->post_modified_gmt === $ctct_list->post_date_gmt ) {

				$list = constantcontact_api()->add_list(
					array(
						'id' => $ctct_list->ID,
						'name' => $ctct_list->post_title,
					)
				);

				add_post_meta( $post_id, '_ctct_list_id', $list->id );

				/**
				 * Hook when a ctct list is saved.
				 *
				 * @since 1.0.0
				 * @param integer $post_id cpt post id
				 * @param integer $list_id ctct list id
				 * @param array $list ctct returned list data
				 */
				do_action( 'ctct_update_list', $post_id, $list_id, $list );

			}
		}
	}

	/**
	 * Update list data cpt and send update request to CTCT
	 *
	 * @since 1.0.0
	 * @param  integer $post_id current post id.
	 * @return void
	 */
	public function update_list( $post_id ) {

		global $pagenow;

		if ( 'post.php' === $pagenow ) {

			$ctct_list = get_post( $post_id );
			$list_id = get_post_meta( $ctct_list->ID, '_ctct_list_id', true );

			if ( $list_id ) {

				$list = constantcontact_api()->update_list(
					array(
						'id' => $list_id,
						'name' => $ctct_list->post_title,
					)
				);

				/**
				 * Hook when a ctct list is updated.
				 *
				 * @since 1.0.0
				 * @param integer $post_id cpt post id
				 * @param integer $list_id ctct list id
				 * @param array $list ctct returned list data
				 */
				do_action( 'ctct_update_list', $post_id, $list_id, $list );

			}
		}
	}

	/**
	 * Delete list from CTCT and database.
	 *
	 * @param  integer $post_id list id.
	 * @return boolean
	 */
	public function delete_list( $post_id ) {

		$list_id = get_post_meta( $post_id, '_ctct_list_id', true );

		if ( $list_id ) {

			$list = constantcontact_api()->delete_list(
				array(
					'id' => $list_id,
				)
			);

			wp_delete_post( $post_id, true );

			/**
			 * Hook when a ctct list is deleted.
			 *
			 * @since 1.0.0
			 * @param integer $post_id
			 * @param integer $list_id
			 */
			do_action( 'ctct_delete_list', $post_id, $list_id );

		}
		return false;
	}

	/**
	 * Returns array of the list data from CTCT
	 *
	 * @since 1.0.0
	 * @return array contact list data from CTCT
	 */
	public function get_lists() {

		$get_lists = array();

		if ( $lists = constantcontact_api()->get_lists() ) {

			foreach ( $lists as $list ) {

				$get_lists[ $list->id ] = $list->name;

			}
		}

		return $get_lists;
	}
}

/**
 * Helper function to get/return the ConstantContact_Lists object.
 *
 * @since 1.0.0
 * @return ConstantContact_Lists object.
 */
function constantcontact_lists() {
	return ConstantContact_Lists::get_instance();
}

// Get it started.
constantcontact_lists();
