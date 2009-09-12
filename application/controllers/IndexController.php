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
 * $Id: IndexController.php 359 2009-07-01 20:28:31Z tim4dev $
 */

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class IndexController extends Zend_Controller_Action
{

	function init()
	{
		$this->view->baseUrl = $this->_request->getBaseUrl();
		// load model
		Zend_Loader::loadClass('Job');
		Zend_Loader::loadClass('Volume');

		$this->view->translate = Zend_Registry::get('translate');
	}

    function indexAction()
    {
    	$config = Zend_Registry::get('config');
    	if ( empty($config->head_title) ) {
    		$this->view->title = "webacula Main Page";
    	} else {
    		$this->view->title = $config->head_title;
    	}
    	
    	$db = Zend_Db_Table::getDefaultAdapter();

    	// *** get info about jobs ***
    	$this->view->titleProblemJobs = $this->view->translate->_("Jobs with errors (last 7 days)");
		// get data from model
    	$jobs = new Job();
    	$ret = $jobs->GetProblemJobs();
    	$this->view->resultProblemJobs = $ret->fetchAll();
    	unset($ret);
    	unset($jobs);

    	// *** get info about volumes ***
    	$this->view->titleProblemVolumes = $this->view->translate->_("Volumes with errors");
		// get data from model
    	$vols = new Volume();
    	$ret = $vols->GetProblemVolumes();
    	$this->view->resultProblemVolumes = $ret->fetchAll();
    	unset($ret);
    	unset($vols);

    	$this->view->titleNextJobs = $this->view->translate->_("Scheduled Jobs (at 24 hours forward)");
    	// get static const
    	$this->view->unknown_volume_capacity = Zend_Registry::get('UNKNOWN_VOLUME_CAPACITY');
    	$this->view->new_volume = Zend_Registry::get('NEW_VOLUME');
    	$this->view->err_volume = Zend_Registry::get('ERR_VOLUME');
		// get data from model
    	$jobs = new Job();
    	$aret = $jobs->GetNextJobs();
    	$this->view->resultNextJobs = $aret;
    	unset($aret);
    	unset($jobs);

    	$this->view->titleRunningJobs = $this->view->translate->_("Information from DB Catalog : List of Running Jobs");
		// get data from model
    	$jobs = new Job();
    	$ret = $jobs->GetRunningJobs();
    	$this->view->resultRunningJobs = $ret->fetchAll();
    	unset($ret);
    	unset($jobs);

    	// get data from Director
    	$jobs = new Job();
    	$this->view->titleDirRunningJobs  = $this->view->translate->_("Information from Director : List of Running Jobs");
		$this->view->resultDirRunningJobs = $jobs->GetDirRunningJobs();
		unset($jobs);

	    $this->view->titleLastJobs = $this->view->translate->_("Terminated Jobs (executed in last 24 hours)");
		// get data from model
    	$jobs = new Job();
    	$ret = $jobs->GetLastJobs();
    	$this->view->resultLastJobs = $ret->fetchAll();
    	unset($ret);
    	unset($jobs);


    	// *** get statistics ***
/*
    	// total clients
    	$select = new Zend_Db_Select($db);
	   	$select->from('Client', array('total_client' => new Zend_Db_Expr(" COUNT(ClientId)") ));
		$this->view->total_client = $db->fetchOne($select);

		// total media
    	$select = new Zend_Db_Select($db);
	   	$select->from('Media', array('total_media' => new Zend_Db_Expr(" COUNT(MediaId)") ));
		$this->view->total_media = $db->fetchOne($select);

		// total pool
    	$select = new Zend_Db_Select($db);
	   	$select->from('Pool', array('total_pool' => new Zend_Db_Expr(" COUNT(PoolId)") ));
		$this->view->total_pool = $db->fetchOne($select);

		// total jobs
    	$select = new Zend_Db_Select($db);
	   	$select->from('Job', array('total_job' => new Zend_Db_Expr(" COUNT(JobId)") ));
		$this->view->total_job = $db->fetchOne($select);

		// total bytes stored
    	$select = new Zend_Db_Select($db);
	   	$select->from('Media', array('total_byte' => new Zend_Db_Expr(" SUM(VolBytes)") ));
		$this->view->total_byte = $db->fetchOne($select);

		// job with errors (last 7 days)
		$last7day = date('Y-m-d H:i:s', time() - 604800); // для совместимости со старыми версиями mysql: NOW() - INTERVAL 7 DAY
    	$select = new Zend_Db_Select($db);
	   	$select->from('Job', array('total_job_error' => new Zend_Db_Expr(" COUNT(JobErrors)") ));
	   	$select->where('((JobErrors > 0) OR (JobStatus IN ("E","e", "f")))');
	   	$select->where("EndTime > ?", $last7day);
		$this->view->total_job_error = $db->fetchOne($select);
*/
    }
}
