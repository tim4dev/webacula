<?php

class WblogbookControllerTest extends ControllerTestCase
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

	public function testPrintableLogbook()
	{
		print "\n".__METHOD__.' ';
		$this->request->setPost(array(
			"date_begin" => date('Y-m-d', time()-2678400),
			"date_end"   => date('Y-m-d', time()),
			"printable_by_date" => 1,
			"test" => 1
		));
		$this->request->setMethod('POST');
		$this->dispatch('wblogbook/filterbydate');
		$this->assertController('wblogbook');
		$this->assertAction('filterbydate');
		//echo $this->response->outputBody();exit; // for debug !!!
		$this->assertResponseCode(200);
		$this->assertQueryContentContains('div', 'Printable version');
		$this->assertQueryCountMin('tr', 5);  // не менее 3 строк таблицы
	}
	
}	