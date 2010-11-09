<?php
/**
 * Copyright 2007, 2008, 2009, 2010 Yuri Timofeev tim4dev@gmail.com
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

class FileController extends MyClass_ControllerAclAction
{
    // for pager
    const ROW_LIMIT_FILES = 500;

    function init ()
    {
        parent::init();
        Zend_Loader::loadClass('Files');
        // for input field validation
        Zend_Loader::loadClass('Zend_Validate');
        Zend_Loader::loadClass('Zend_Filter_Input');
        Zend_Loader::loadClass('Zend_Validate_StringLength');
        Zend_Loader::loadClass('Zend_Validate_NotEmpty');
        Zend_Loader::loadClass('Zend_Validate_Digits');
        $validators = array('*' => array(new Zend_Validate_StringLength(1, 255) , 'Digits') , 'jobid' => array('NotEmpty'));
        $filters = array('*' => 'StringTrim');
        $this->input = new Zend_Filter_Input($filters, $validators);
    }

    function listAction ()
    {
        // http://localhost/webacula/file/list/jobid/2234/page/1
        $this->input->setData(array('jobid' => $this->_request->getParam('jobid') , 'page' => $this->_request->getParam('page', 1)));
        if ($this->input->isValid()) {
            $jobid = $this->input->getEscaped('jobid');
            // unused ? $page = $this->input->getEscaped('page', 1);
        } else {
            $this->view->result = null;
            return;
        }
        $this->view->titleFile = $this->view->translate->_("List Files for JobId") . " " . $jobid;
        $files = new Files();
        $select = $files->getSelectFilesByJobId($jobid);  // do Bacula ACKs
        //echo '<pre>',$select->__toString(),'</pre>'; exit; // for !!!debug!!!
        $paginator = Zend_Paginator::factory($select);
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        $paginator->setItemCountPerPage(self::ROW_LIMIT_FILES);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $this->view->paginator = $paginator;
        $paginator->setView($this->view);
    }

}