<?php
/**
 * Copyright 2009, 2010 Yuri Timofeev tim4dev@gmail.com
 *
 * Webacula is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Webacula is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webacula.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
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
        // layout
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('main');
        $this->_config = Zend_Registry::get('config');
        // debug
        if ( $this->_config->debug_level > 0 ) {
            Zend_Loader::loadClass('Zend_Log_Writer_Stream');
            Zend_Loader::loadClass('Zend_Log');
            $writer = new Zend_Log_Writer_Stream(self::DEBUG_LOG);
            $this->logger = new Zend_Log($writer);
        }
    }

}
