<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

require_once dirname(__FILE__) . '/application/bootstrap.php';
require_once dirname(__FILE__) . '/application/controllers/AllTests.php';
require_once dirname(__FILE__) . '/application/models/AllTests.php';

/* какие каталоги учитывать при построении отчета */
if ( PHPUnit_Runner_Version::id() >= '3.5.0' ) {
    // PHPUnit 3.5.5
    PHP_CodeCoverage_Filter::getInstance()->addDirectoryToWhitelist('../application');
    PHP_CodeCoverage_Filter::getInstance()->removeFileFromWhitelist('../application/views/helpers');
} else {
    // PHPUnit 3.4
    PHPUnit_Util_Filter::addDirectoryToWhitelist('../application');
    PHPUnit_Util_Filter::removeFileFromWhitelist('../application/views/helpers');
}


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
