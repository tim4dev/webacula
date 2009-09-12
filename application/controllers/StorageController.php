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
 * $Id: StorageController.php 359 2009-07-01 20:28:31Z tim4dev $
 */

require_once 'Zend/Controller/Action.php';

class StorageController extends Zend_Controller_Action
{

	function init()
	{
		$this->view->baseUrl = $this->_request->getBaseUrl();
		// load model
		Zend_Loader::loadClass('Storage');
		$this->view->translate = Zend_Registry::get('translate');
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

	    	$config = Zend_Registry::get('config');

	    	// check access to bconsole

    		if ( !file_exists($config->bacula->bconsole))	{
    			$this->view->result = 'NOFOUND';
    			return;
    		}

    		$bconsolecmd = '';
            if ( isset($config->bacula->sudo))	{
                // run with sudo
                $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            } else {
                $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            }
            $command_output = '';
            $return_var = 0;
exec($bconsolecmd . " <<EOF
status storage=$storage_name
quit
EOF", $command_output, $return_var);

			// check return status of the executed command
    		if ( $return_var != 0 )	{
    			$this->view->result = 'ERR';
    			return;
    		}

    		$this->view->result = $command_output;
    	}
    	else
    		$this->view->result = null;
    }


    function actMountAction()
    {
    	$action = addslashes( $this->_request->getParam('act') );
    	if ( ($action != 'mount') && ($action != 'umount') ) unset($action);

    	$storage_name = addslashes( $this->_request->getParam('name') );

    	if ( !empty($action) &&  !empty($storage_name) )	{
    	    $this->view->title = $this->view->translate->_("Storage") . " " . $storage_name . " " . $action;

	    	$config = Zend_Registry::get('config');

	    	// check access to bconsole
    		if ( !file_exists($config->bacula->bconsole))	{
    			$this->view->result = 'NOFOUND';
    			return;
    		}

    		$bconsolecmd = '';
            if ( isset($config->bacula->sudo))	{
                // run with sudo
                $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            } else {
                $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
            }
            $command_output = '';
            $return_var = 0;

exec($bconsolecmd . " <<EOF
$action storage=\"$storage_name\"
.
quit
EOF", $command_output, $return_var);

			// check return status of the executed command
    		if ( $return_var != 0 )	{
    			$this->view->result = 'ERR';
    			return;
    		}

    		$this->view->result = $command_output;
    	}
    	else
    		$this->view->result = null;
    }

}
