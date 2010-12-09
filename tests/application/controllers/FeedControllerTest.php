<?php
class FeedControllerTest extends ControllerTestCase
{
    /**
     * @group feed
     */
    public function testRSS ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $this->dispatch('feed/feed/test/1');
        $this->_isLogged($this->response->outputBody());
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('feed');
        $this->assertAction('feed');
        $this->assertResponseCode(200);
        $this->assertQueryContentContains('title', '[CDATA[My Bacula backup server #1]]');
    }
}