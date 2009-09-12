<?php
/**
 * Copyright 2007, 2008 Yuri Timofeev tim4dev@gmail.com
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
 * $Id: LogController.php 359 2009-07-01 20:28:31Z tim4dev $
 */

/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class LogController extends Zend_Controller_Action
{

	function init()
	{
		$this->view->baseUrl = $this->_request->getBaseUrl();
		Zend_Loader::loadClass('Log');
		$this->view->translate = Zend_Registry::get('translate');
	}

    function viewLogIdAction()
    {
    	// http://localhost/webacula/log/jobid/<JobId>/jobname/<JobName>
    	$job_id   = intval( $this->_request->getParam('jobid') );
    	$job_name = addslashes( $this->_request->getParam('jobname') );
    	if ( !empty($job_id)  )	{
    		$this->view->title = sprintf($this->view->translate->_("Console messages for Job %s, JobId %u"), $job_name, $job_id);

			$db = Zend_Db_Table::getDefaultAdapter();
			// make select from multiple tables
			$select = new Zend_Db_Select($db);
    		$select->distinct();
    		$select->from(array('l' => 'Log'), array('LogId', 'JobId', 'LogTime' => 'Time', 'LogText'));

    		$select->where("JobId='$job_id'"); // AND

			$select->order(array('LogId', 'LogTime'));

			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$this->view->result = $stmt->fetchAll();

    	}
    	else
    		$this->view->result = null;
    }


}