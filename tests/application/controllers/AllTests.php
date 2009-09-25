<?php
require_once dirname ( __FILE__ ) . '/IndexControllerTest.php';
require_once dirname ( __FILE__ ) . '/JobControllerTest.php';
require_once dirname ( __FILE__ ) . '/VolumeControllerTest.php';
require_once dirname ( __FILE__ ) . '/ChartControllerTest.php';
require_once dirname ( __FILE__ ) . '/DirectorControllerTest.php';
require_once dirname ( __FILE__ ) . '/FileControllerTest.php';
require_once dirname ( __FILE__ ) . '/LogControllerTest.php';
require_once dirname ( __FILE__ ) . '/OtherControllerTest.php';

class ControllersAllTests {
	
	public static function main() {
		PHPUnit_TextUI_TestRunner::run ( self::suite () );
	}
	
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite ( 'Webacula Test Suite - Controllers' );
		$suite->addTestSuite ( 'IndexControllerTest' );
		$suite->addTestSuite ( 'DirectorControllerTest' );
		$suite->addTestSuite ( 'JobControllerTest' );
		$suite->addTestSuite ( 'VolumeControllerTest' );
		$suite->addTestSuite ( 'FileControllerTest' );
		$suite->addTestSuite ( 'LogControllerTest' );
		$suite->addTestSuite ( 'ChartControllerTest' );
		$suite->addTestSuite ( 'OtherControllerTest' );
		return $suite;
	}
}
