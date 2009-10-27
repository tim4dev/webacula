<?php
class VolumeControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    /**
     * @access protected
     */
    protected function tearDown ()
    {
        $this->resetRequest();
        $this->resetResponse();
        parent::tearDown();
    }


    /**
     * @group volume
     */
    public function testFindPoolById ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('volume/find-pool-id/id/2/name/pool.file.7d');
        $this->assertController('volume');
        $this->assertAction('find-pool-id');
        //echo $this->response->outputBody(); // for debug !!!
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertResponseCode(200);
        $this->assertNotQueryContentContains('div', 'No Volumes found');
        $this->assertQueryContentContains('td', 'pool.file.7d.0001');
        $this->assertQueryContentContains('td', 'pool.file.7d.0002');
    }


    /**
     * @group volume
     */
    public function testDetail ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->dispatch('volume/detail/mediaid/1');
        $this->assertController('volume');
        $this->assertAction('detail');
        $this->assertResponseCode(200);
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertNotQueryContentContains('div', 'No Volumes found');
        $this->assertQueryContentContains('td', 'pool.file.7d.0001');
    }


    /**
     * @group volume
     */
    public function testUpdate ()
    {
        print "\n" . __METHOD__ . ' ';
        // update record
        $this->request->setPost(array('mediaid' => 1 , // mandatory attribute
            'poolid' => 1 , // Volume moved to another Pool (old PoolId = 2)
            'volstatus' => 'Append' ,
            'volretention' => 365 ,
            'recycle' => 1 ,
            'slot' => 1 ,
            'inchanger' => 0 ,
            'maxvoljobs' => 99999999 ,
            'maxvolfiles' => 99999 ,
            'comment' => "\n\nLorem ipsum dolor sit amet\n"));
        $this->request->setMethod('POST');
        $this->dispatch('volume/update');
        $this->assertController('volume');
        $this->assertAction('update');
        // backtracking
        $this->request->setPost(array('mediaid' => 1 , // mandatory attribute
            'poolid' => 2 , // mandatory attribute
            'volstatus' => 'Append' ,
            'volretention' => 365 ,
            'recycle' => 1 ,
            'slot' => 1 ,
            'inchanger' => 0 ,
            'maxvoljobs' => 99999999 ,
            'maxvolfiles' => 99999 ,
            'comment' => "\n\nLorem ipsum dolor sit amet\n"));
        $this->request->setMethod('POST');
        $this->dispatch('volume/update');
        $this->assertController('volume');
        $this->assertAction('update');
    }

}
