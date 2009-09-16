<?php
/**
 *
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
 * $Id: DirectorController.php 359 2009-07-01 20:28:31Z tim4dev $
 */
/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class DirectorController extends Zend_Controller_Action
{

	function init()
	{
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->translate = Zend_Registry::get('translate');
		Zend_Loader::loadClass('Director');
	}

    function statusdirAction()
    {
    	$this->view->title = $this->view->translate->_("Status Director");
        // get status dir
        $director = new Director();           
		if ( !$director->isFoundBconsole() )	{
			$this->view->result_error = 'NOFOUND_BCONSOLE';
   		  	$this->render();
   		  	return;
   	    }
		$astatusdir = $director->execDirector(
"<<EOF
status dir
@quit
EOF"
		); 
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] != 0 )	{
			$this->view->result_error = $astatusdir['result_error'];
		}
		return;
    }
}
