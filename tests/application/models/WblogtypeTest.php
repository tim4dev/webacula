<?php

require_once APPLICATION_PATH . '/models/Wblogtype.php';
require_once 'PHPUnit/Framework/TestCase.php';

class WblogtypeTest extends PHPUnit_Framework_TestCase {
	
	private $logtype;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->logtype = new wbLogtype();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->logtype = null;
		parent::tearDown();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// empty
	}

	public function testIndex() {
		print "\n".__METHOD__.' ';
		$result = $this->logtype->fetchAll();
		$this->assertGreaterThanOrEqual(4, sizeof($result), 'error select logtype');
	}

}
