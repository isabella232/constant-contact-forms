<?php
/**
 * @package ConstantContact_Tests
 * @subpackage Connect
 * @author Pluginize
 * @since 1.0.0
 */

class ConstantContact_Connect_Test extends WP_UnitTestCase {

	function setup() {
		parent::setup();
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'ConstantContact_Connect' ) );
	}

	function test_maybe_disconnect() {

	}

	function teardown() {
		parent::teardown();
	}
}
