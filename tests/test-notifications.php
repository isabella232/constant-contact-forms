<?php
/**
 * @package ConstantContact_Tests
 * @subpackage Notifications
 * @author Pluginize
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

class ConstantContact_Notifications_Test extends TestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'ConstantContact_Notifications' ) );
	}

	function test_class_access() {
		$this->assertTrue( constant_contact()->notifications instanceof ConstantContact_Notifications );
	}
}
