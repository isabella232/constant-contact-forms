<?php

class ConstantContact_Mail_Test extends WP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'ConstantContact_Mail' ) );
	}

	function test_class_access() {
		$this->assertTrue( constant_contact()->mail instanceof ConstantContact_Mail );
	}

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}
}
