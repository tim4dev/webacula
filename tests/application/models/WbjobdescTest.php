<?php
require_once APPLICATION_PATH . '/models/Wbjobdesc.php';
require_once 'PHPUnit/Framework/TestCase.php';
class WbjobdescTest extends PHPUnit_Framework_TestCase
{
    
    private $jobdesc;
    private $id_new;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        $this->jobdesc = new wbJobDesc();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->jobdesc = null;
        $this->id_new = null;
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct ()
    {    // empty
    }

    /**
     * @group jobdesc
     */
    public function testInsertAndUpdateRecord ()
    {
        print "\n" . __METHOD__ . ' ';
        // insert
        $data = array(
            'name_job'    => 'job-name-test-3',
            'short_desc'  => 'Important billing DB',
            'description' => 'PHPUnit test',
            'retention_period' => '3 days'
        );
        $this->id_new = $this->jobdesc->insert($data); // возвращает id вставленной записи
        $this->assertNotNull($this->id_new);
        // update
        $data = array('retention_period' => '333 days');
        $where = $this->jobdesc->getAdapter()->quoteInto('desc_id = ?', $this->id_new);
        $res = $this->jobdesc->update($data, $where); // возвращает кол-во измененных записей
        $this->assertEquals(1, $res, 'error update record to jobdesc');
    }
    
    
    /**
     * @group jobdesc
     */
    public function testIndex ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->jobdesc = new wbJobDesc();
        $result = $this->jobdesc->fetchAll();
        $this->assertGreaterThanOrEqual(1, sizeof($result), 'error job desc');
    }

    
}