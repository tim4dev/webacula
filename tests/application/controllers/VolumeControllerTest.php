<?php

class VolumeControllerTest extends ControllerTestCase
{
	/**
	 * @access protected
	 */
	protected function tearDown() {
		$this->resetRequest ();
		$this->resetResponse ();
		parent::tearDown ();
	}
	
	public function testFindPoolById() {
		print "\n" . __METHOD__ . ' ';
		$this->dispatch ( 'volume/find-pool-id/id/2/name/pool.file.7d' );
		$this->assertController ( 'volume' );
		$this->assertAction ( 'find-pool-id' );
		//echo $this->response->outputBody(); // for debug !!!
		$this->assertResponseCode ( 200 );
		$this->assertNotQueryContentContains ( 'div', 'No Volumes found' );
		$this->assertQueryContentContains ( 'td', 'pool.file.7d.0001' );
		$this->assertQueryContentContains ( 'td', 'pool.file.7d.0002' );
	}
	
}	