<?php
require_once dirname(__FILE__) . '/IndexControllerTest.php';
require_once dirname(__FILE__) . '/JobControllerTest.php';
require_once dirname(__FILE__) . '/ChartControllerTest.php';
require_once dirname(__FILE__) . '/DirectorControllerTest.php';
require_once dirname(__FILE__) . '/OtherControllerTests.php';

class ControllersAllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Webacula Test Suite - Controllers');
        $suite->addTestSuite('IndexControllerTest');
        $suite->addTestSuite('DirectorControllerTest');
        $suite->addTestSuite('JobControllerTest');
		  $suite->addTestSuite('ChartControllerTest');
        $suite->addTestSuite('OtherControllerTests');
        return $suite;
    }
}
