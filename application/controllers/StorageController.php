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

class StorageController extends Zend_Controller_Action
{

	function init()
	{
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->translate = Zend_Registry::get('translate');
		// load model
		Zend_Loader::loadClass('Storage');
		Zend_Loader::loadClass('Director');
	}

    function storageAction()
    {
        $this->view->title = $this->view->translate->_("Storages");
    	// get data for form
    	$storages = new Storage();
    	$this->view->storages = $storages->fetchAll();
    }


    function statusIdAction()
    {
    	$storage_id   = intval( $this->_request->getParam('id') );
    	$storage_name = addslashes( $this->_request->getParam('name') );

    	if ( !empty($storage_id)  )	{
    	    $this->view->title = sprintf($this->view->translate->_("Storage %s status"), $storage_name);
			$director = new Director();           
			if ( !$director->isFoundBconsole() )	{
				$this->view->result_error = 'NOFOUND_BCONSOLE';
   		  		$this->render();
   		  		return;
   	    	}
			$astatusdir = $director->execDirector(
" <<EOF
status storage=\"$storage_name\"
quit
EOF"
			);
			$this->view->command_output = $astatusdir['command_output'];		
	        // check return status of the executed command
    	    if ( $astatusdir['return_var'] != 0 )	{
				$this->view->result_error = $astatusdir['result_error'];
				$this->render();
				return;
			}
    	}
    	else
    		$this->view->command_output = null;
    }



    function actMountAction()
    {
    	$action = addslashes( $this->_request->getParam('act') );
    	if ( ($action != 'mount') && ($action != 'umount') ) unset($action);

    	$storage_name = addslashes( $this->_request->getParam('name') );

    	if ( !empty($action) &&  !empty($storage_name) )	{
    	    $this->view->title = $this->view->translate->_("Storage") . " " . $storage_name . " " . $action;
			$director = new Director();           
			if ( !$director->isFoundBconsole() )	{
				$this->view->result_error = 'NOFOUND_BCONSOLE';
   		  		$this->render();
   		  		return;
   	    	}		
			$astatusdir = $director->execDirector(
" <<EOF
$action storage=\"$storage_name\"
.
quit
EOF"
			);
			$this->view->command_output = $astatusdir['command_output'];		
	        // check return status of the executed command
    	    if ( $astatusdir['return_var'] != 0 )	{
				$this->view->result_error = $astatusdir['result_error'];
				$this->render();
				return;
			}
    	}
    	else
    		$this->view->command_output = null;
    }

}
