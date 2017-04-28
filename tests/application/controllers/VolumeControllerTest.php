<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class VolumeControllerTest extends ControllerTestCase
{
    const ZF_pattern = '/Exception:|Warning:|Notice:|Call Stack/'; // Zend Framework

    /**
     * @group volume
     */
    public function testFindPoolById ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('volume/find-pool-id/id/1/name/pool.file.7d');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('volume');
        $this->assertAction('find-pool-id');
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
        $this->_rootLogin();
        $this->dispatch('volume/detail/mediaid/1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
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
        $this->_rootLogin();
        $volname = 'pool.file.7d.0001';
        // get info
        $media = new Media();
        $res = $media->getByName($volname);
        $mediaid = $res[0]['mediaid'];
        $poolid  = $res[0]['poolid'];
        // update record
        $this->request->setPost(array('mediaid' => $mediaid , // mandatory attribute
            'poolid' => 2 , // Volume moved to another Pool (old PoolId = 1)
            'volstatus' => 'Append' ,
            'volretention' => 365 ,
            'recycle' => 1 ,
            'slot' => 1 ,
            'inchanger' => 0 ,
            'maxvoljobs' => 99999999 ,
            'maxvolfiles' => 99999 ,
            'comment' => "\n\nmoved\nLorem ipsum dolor sit amet\n"));
        $this->request->setMethod('POST');
        $this->dispatch('volume/update');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('volume');
        $this->assertAction('update');
        // back      
        $this->resetRequest()
             ->resetResponse();
        $this->request->setPost(array('mediaid' => $mediaid , // mandatory attribute
            'poolid' => $poolid , // mandatory attribute
            'volstatus' => 'Append' ,
            'volretention' => 365 ,
            'recycle' => 1 ,
            'slot' => 1 ,
            'inchanger' => 0 ,
            'maxvoljobs' => 999 ,
            'maxvolfiles' => 999 ,
            'comment' => "\n\nback\nLorem ipsum dolor sit amet\n"));
        $this->request->setMethod('POST');
        $this->dispatch('volume/update');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        $this->assertController('volume');
        $this->assertAction('update');
    }

}
