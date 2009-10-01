<?php
/**
 * Copyright 2007, 2008 2009 Yuri Timofeev tim4dev@gmail.com
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
 * $Id: HelpController.php 398 2009-08-13 23:07:32Z tim4dev $
 */
/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class HelpController extends Zend_Controller_Action
{

	function init()
	{
	    $this->_helper->viewRenderer->setNoRender(); // disable autorendering
		$this->view->baseUrl = $this->_request->getBaseUrl();
	}

    function indexAction()
    {
    	$unit_test = $this->_request->getParam('test', null);
    	$this->view->title = "webacula help";
    	$config = Zend_Registry::get('config');
    	if ( empty($unit_test) ) {
         if ( isset($config->locale) ) {
            $user_language =  addslashes(trim($config->locale));
         } else {
            // autodetect
            $locale = new Zend_Locale(Zend_Locale::BROWSER);
            $user_language = $locale->getLanguage(); // 'ru', 'en'...
            //print_r($user_language); //!! for debug
         }
    	} else {
    		$user_language = 'en'; // for unit tests
    	}

        $namefile = 'help/index_' . $user_language . '.phtml';

        if ( !file_exists("../application/views/scripts/" .$namefile) )  {
            $namefile = 'help/index_en.phtml'; // default language
        }
        
        Zend_Loader::loadClass('Zend_Version');   
        $this->view->zend_version = Zend_Version::VERSION;
        $this->view->db_adapter_bacula   = Zend_Registry::get('DB_ADAPTER');       
	    $db = Zend_Registry::get('db_bacula');
	    $this->view->db_server_version_bacula = $db->getServerVersion();
	    
	    $this->view->db_adapter_webacula = Zend_Registry::get('DB_ADAPTER_WEBACULA');
	    $db_webacula = Zend_Registry::get('db_webacula');
	    $this->view->db_server_version_webacula = $db_webacula->getServerVersion();

        echo $this->renderScript($namefile);
        return;
    }

    function myPhpInfoAction()
    {
        echo $this->render();
        return;
    }

    function myIdAction()
    {
        echo $this->render();
        return;
    }

}
