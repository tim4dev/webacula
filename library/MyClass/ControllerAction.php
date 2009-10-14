<?php

/**
 * Common code for all Controllers
 */

require_once 'Zend/Controller/Action.php';

class MyClass_ControllerAction extends Zend_Controller_Action
{
    
    const DEBUG_LOG = '/tmp/webacula_debug.log';
    protected $_config;
    public $debug_level;

    public function init()
    {
        parent::init();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->view->translate = Zend_Registry::get('translate');
        $this->view->language  = Zend_Registry::get('language');
        
        $this->_config = Zend_Registry::get('config');
        // debug
        if ( $this->_config->debug_level > 0 ) {
            Zend_Loader::loadClass('Zend_Log_Writer_Stream');
            Zend_Loader::loadClass('Zend_Log');
            $writer = new Zend_Log_Writer_Stream(self::DEBUG_LOG);
            $this->logger = new Zend_Log($writer);
        }
    }

    public function __call( $method, $args )
    {
        if ( $this->_config->debug_level ) {
            $this->logger->log("Action $method does not exist", Zend_Log::INFO);
            parent::__call( $method, $args );
        } else {
            if('Action' == substr($method, -6)) {
                $this->_forward('index');
            }
        }
    }
    
}
?>
