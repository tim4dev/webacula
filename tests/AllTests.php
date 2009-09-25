<?php
require_once dirname(__FILE__) . '/application/bootstrap.php';
require_once dirname(__FILE__) . '/application/controllers/AllTests.php';
require_once dirname(__FILE__) . '/application/models/AllTests.php';

/* какие каталоги учитывать при построении отчета */
PHPUnit_Util_Filter::addDirectoryToWhitelist("../application");
PHPUnit_Util_Filter::addDirectoryToWhitelist("../library/MyClass");
PHPUnit_Util_Filter::addDirectoryToWhitelist("../library/Other");

class AllTests
{
	public static function main() {
		$parameters = array ();
		PHPUnit_TextUI_TestRunner::run ( self::suite (), $parameters );
	}
	
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite ( 'Webacula Test Suite' );		
		$suite->addTest ( ControllersAllTests::suite () );
		$suite->addTest ( ModelsAllTests::suite () );
		return $suite;
	}
}
