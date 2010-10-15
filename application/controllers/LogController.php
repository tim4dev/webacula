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

class LogController extends MyClass_ControllerAclAction
{

    function init ()
    {
        parent::init();
        Zend_Loader::loadClass('Log');
    }

    function viewLogIdAction ()
    {
        // http://localhost/webacula/log/jobid/<JobId>/jobname/<JobName>
        $job_id = intval($this->_request->getParam('jobid'));
        $job_name = addslashes($this->_request->getParam('jobname'));
        if (! empty($job_id)) {
            $this->view->title = sprintf($this->view->translate->_("Console messages for Job %s, JobId %u"), $job_name, $job_id);
            $log = new Log();
            $this->view->result = $log->getById($job_id);
        } else
            $this->view->result = null;
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

}