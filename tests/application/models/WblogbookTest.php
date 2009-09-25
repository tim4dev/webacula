<?php

require_once APPLICATION_PATH . '/models/Wblogbook.php';
require_once 'PHPUnit/Framework/TestCase.php';

class WblogbookTest extends PHPUnit_Framework_TestCase {
	
	private $logbook;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		$this->logbook = new wbLogBook();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		$this->logbook = null;
		parent::tearDown();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// empty
	}

	/**
	 * @group logbook
	 */
	public function testIndex() {
		print "\n".__METHOD__.' ';
		$ret = $this->logbook->IndexLogBook(null, null, null);
		$result = $ret->fetchAll();
		$this->assertGreaterThan(3, sizeof($result), 'error select logbook');
	}
	
	/**
	 * @group logbook
	 */
	public function testAddRecord() {
		print "\n".__METHOD__.' ';
		$data = array(
         'logDateCreate' => date("Y-m-d H:i:s", time() ),
         'logTxt'    => 'PHPUnit test testAddRecord',
         'logTypeId' => 20
      );
      $id = $this->logbook->insert($data);  // возвращает id вставленной записи
      $this->assertGreaterThan(3, $id, 'error insert record to logbook');
	}
	
	/**
	 * @group logbook
	 */
	public function testUpdateRecord() {
		print "\n".__METHOD__.' ';
      $data = array(
         'logDateCreate' => date('Y-m-d H:i:s', time()-300),
         'logDateLast'   => date('Y-m-d H:i:s', time()),
         'logTypeId'     => 30,
         'logTxt'        => 'PHPUnit test testUpdateRecord',
         'logIsDel'      => 0
      );
      $where = $this->logbook->getAdapter()->quoteInto('logId = ?', 6);
      $res = $this->logbook->update($data, $where); // возвращает кол-во измененных записей
      $this->assertEquals(1, $res, 'error update record to logbook');
	}
	
}
