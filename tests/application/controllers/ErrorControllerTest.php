<?php

class ErrorControllerTest extends ControllerTestCase
{

   /**
    * @group error
    */
   public function testErrorAction() {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('index/fake_action');
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('error');
      $this->assertAction('error');
      $this->assertResponseCode(404);
      $this->assertQueryContentContains('p', 'Action "fakeaction" does not exist');
   }

   /**
    * @group error
    */
   public function testErrorBoth() {
      print "\n".__METHOD__.' ';
      $this->_rootLogin();
      $this->dispatch('fake_controller/fake_action');
      $this->logBody( $this->response->outputBody() ); // debug log
      $this->assertModule('default');
      $this->assertController('error');
      $this->assertAction('error');
      $this->assertResponseCode(404);
      $this->assertQueryContentContains('p', 'Invalid controller specified (fake_controller)');
   }


}
