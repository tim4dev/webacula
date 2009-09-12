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
 * $Id: FileController.php 359 2009-07-01 20:28:31Z tim4dev $
 */
/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class FileController extends Zend_Controller_Action
{
	// for pager
	const ROW_LIMIT_FILES = 500;

	function init()
	{
	    $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->translate = Zend_Registry::get('translate');

		Zend_Loader::loadClass('Zend_Paginator');
		// for input field validation
        Zend_Loader::loadClass('Zend_Validate');
        Zend_Loader::loadClass('Zend_Filter_Input');
        Zend_Loader::loadClass('Zend_Validate_StringLength');
        Zend_Loader::loadClass('Zend_Validate_NotEmpty');
        Zend_Loader::loadClass('Zend_Validate_Digits');
        $validators = array(
            '*' => array(
                new Zend_Validate_StringLength(1, 255),
                'Digits'
            ),
            'jobid'=> array(
                'NotEmpty'
            )
        );
        $filters = array(
            '*'  => 'StringTrim'
        );
        $this->input = new Zend_Filter_Input($filters, $validators);
	}


    function listAction()
    {
    	// http://localhost/webacula/file/list/jobid/2234/page/1
        $this->input->setData( array('jobid' => $this->_request->getParam('jobid'),
                    'page' => $this->_request->getParam('page', 1)) );
        if ( $this->input->isValid() ) {
            $jobid = $this->input->getEscaped('jobid');
            $page  = $this->input->getEscaped('page', 1);
        } else {
            $this->view->result = null;
            return;
        }

   		$this->view->titleFile = $this->view->translate->_("List Files for JobId") . " " . $jobid;
		$db = Zend_Db_Table::getDefaultAdapter();

   		// make select from multiple tables
   		// !!! IMPORTANT !!! с Zend Paginator нельзя использовать DISTINCT иначе не работает в PDO_PGSQL
   		$select = new Zend_Db_Select($db);
   		$select->from(array('f' => 'File'), array('FileId', 'FileIndex', 'LStat'));
   		$select->joinLeft(array('p' => 'Path'), 'f.PathId = p.PathId' ,array('Path'));
   		$select->joinLeft(array('n' => 'Filename'), 'f.FileNameId = n.FileNameId',array('Name'));
		$select->where("f.JobId = $jobid");
		$select->order(array('f.FileIndex', 'f.FileId'));

		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

		$paginator = Zend_Paginator::factory($select);
		Zend_Paginator::setDefaultScrollingStyle('Sliding');
		$paginator->setItemCountPerPage(self::ROW_LIMIT_FILES);
		$paginator->setCurrentPageNumber($this->_getParam('page', 1));
		$this->view->paginator = $paginator;
		$paginator->setView($this->view);
    }

}