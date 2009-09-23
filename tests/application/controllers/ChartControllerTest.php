<?php

class ChartControllerTest extends ControllerTestCase
{
   /**
    * @access protected
    */
	protected function tearDown()
	{
		$this->resetRequest();
        $this->resetResponse();
        parent::tearDown();
	}
	 
	public function testTimeline()
	{
		print "\n".__CLASS__."\t".__FUNCTION__.' ';
		$name_img = 'img_tmp.png';
		if ( file_exists($name_img) ) {
			unlink($name_img);	
		}
		$this->assertTrue(function_exists("imagepng"), "(imagepng not found)");
        $this->dispatch('chart/timeline/test/1/datetimeline/' . date("Y-m-d", time()));
		$this->assertModule('default');
        $this->assertController('chart');
        $this->assertAction('timeline');
		$img = $this->response->outputBody();
		if ( empty($img) ) {
			$this->assertTrue(FALSE, "image is empty!");
		}	
		$f = fopen($name_img, 'w');
		$res = fwrite($f, $img);
		if ( !$res ) {
			$this->assertTrue(FALSE, "file $name_img can't writing!");
		}
		fclose($f);
		$this->assertNotNull($size = GetImageSize($name_img));	
		$this->assertGreaterThan(700, $size[0]); // width
		$this->assertGreaterThan(400,  $size[1]); // height
		unlink($name_img);
		$this->assertFileNotExists($name_img, "file $name_img not deleted!");
	}
	
}
