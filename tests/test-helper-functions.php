<?php
/**
 * @package ConstantContact_Tests
 * @subpackage HelperFunctions
 * @author Pluginize
 * @since 1.0.0
 */

class ConstantContact_Helper_Functions_Test extends WP_UnitTestCase {

	/**
	 * The option name used by constant_contact_set_has_exceptions().
	 */
	const EXCEPT_OPTION_NAME = 'ctct_exceptions_exist';

	/**
	 * Runs the routine before each test is executed.
	 *
	 * @since NEXT
	 */
	public function setUp() {
		// This code will run before each test.
		parent::setUp();
	}

	/**
	 * After a test method runs, resets any state in WordPress the test method might have changed.
	 *
	 * @since NEXT
	 */
	public function tearDown() {
		// This code will run after each test.
		delete_option( self::EXCEPT_OPTION_NAME );
		parent::tearDown();
	}
}
