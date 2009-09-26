<?php

class ErrorControllerTest extends ControllerTestCase
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

   public function testError() {
      print "\n".__METHOD__.' ';
      $this->dispatch('fake_controller/fake_action');
      //echo $this->response->outputBody(); // for debug !!!
      $this->assertModule('default');
      $this->assertController('error');
      $this->assertAction('error');
      $this->assertResponseCode(200);
      $this->assertQueryContentContains('p', 'Invalid controller specified (fake_controller)');
   }


}
