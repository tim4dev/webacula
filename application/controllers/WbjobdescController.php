<?php
/**
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This file is part of Webacula.
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


require_once 'Zend/Controller/Action.php';

class WbjobdescController extends MyClass_ControllerAction
{
    
    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Wbjobdesc'); // load model
        Zend_Loader::loadClass('FormJobdesc');
        $this->config_webacula = Zend_Registry::get('config_webacula');
    }
    
    
    public function indexAction()
    {
        $this->view->title = $this->view->translate->_("Job Descriptions");
        // get data from model
        $jobdesc = new wbJobDesc();
        $this->view->result = $jobdesc->fetchAll(null, array('name_job', 'desc_id') );        
    }

    
    public function addAction()
    {       
        $this->view->title = $this->view->translate->_("Add Job Description");
        $form = new formJobdesc();
        if ( $this->_request->isPost() &&  ($this->_request->getParam('form1') == '1') ) {
//        if ( $this->_request->isPost() ) {            
            if ( $form->isValid($this->_getAllParams()) ) {
                $name_job = stripslashes( trim( $this->_request->getParam('name_job') ) );
                $description = stripslashes( trim( $this->_request->getParam('description') ) );
                $retention_period = stripslashes( trim( $this->_request->getParam('retention_period') ) );
                $table = new wbJobDesc();
                $data = array(
                    'name_job' => $name_job,
                    'description' => $description,
                    'retention_period' => $retention_period
                );
                $rows_affected = $table->insert($data);
                if ($rows_affected) {
                    $this->_helper->redirector('index'); // action, controller
                    return;
                }
            }
        }
        $this->view->form = $form;
    }
    
    
}