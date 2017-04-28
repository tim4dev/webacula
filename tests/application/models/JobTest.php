<?php

require_once APPLICATION_PATH . '/models/Job.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Job model test case.
 */
class JobTest extends ModelTestCase {

    private $job;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() {
        parent::setUp ();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        $this->job = null;
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct() {
        // empty
        parent::__construct();
    }


    /**
     * @group find-job
     */
    public function testGetByFileName() {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $limit = 100;
        // test
        // getByFileName($path, $namefile, $client, $limit, $type_search)
        echo "\n\t* Ordinary search";  // $result = id 5, 8
        $this->job = new Job();
        $result = $this->job->getByFileName('/tmp/webacula/test/1/', 'file_new11.dat', '', $limit, 'ordinary');
        $this->assertEquals(2, sizeof($result), 'error');
        echo " - OK";

        echo "\n\t* LIKE search"; // $result = id 2
        $result = $this->job->getByFileName("/tmp/webacula/test/1/0 Каталог'tmp/%/", "100_Файл%txt", '', $limit, 'like');
        $this->assertEquals(1, sizeof($result), 'error');
        echo " - OK";

        if ( $this->job->db_adapter != 'PDO_SQLITE' ) {
            echo "\n\t* REGEXP search"; // $result = id 2
            $result = $this->job->getByFileName("", "^f.*dat$", '', $limit, 'regexp');
            $this->assertEquals(34, sizeof($result), 'error'); // only terminated jobs
            echo " - OK";
        }
    }


}
