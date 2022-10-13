<?php
/**
 * Copyright 2009, 2010 Yuriy Timofeev tim4dev@gmail.com
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
require_once dirname(__FILE__) . '/WbTmpTableTest.php';
require_once dirname(__FILE__) . '/WblogbookTest.php';
require_once dirname(__FILE__) . '/WblogtypeTest.php';
require_once dirname(__FILE__) . '/WbjobdescTest.php';
require_once dirname(__FILE__) . '/JobTest.php';

class ModelsAllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Webacula Test Suite - Models');
        $suite->addTestSuite('WbTmpTableTest');
        $suite->addTestSuite('WblogbookTest');
        $suite->addTestSuite('WblogtypeTest');
        $suite->addTestSuite('WbjobdescTest');
        $suite->addTestSuite('JobTest');
        return $suite;
    }
}
