<?php
/**
 * Copyright 2007, 2008 2009, 2010 Yuri Timofeev tim4dev@gmail.com
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

class HelpController extends MyClass_ControllerAclAction
{

    function init ()
    {
        parent::init();
        $this->view->locale = Zend_Registry::get('locale');
    }

    function indexAction ()
    {
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->setLayout('main');
        }
        $this->view->title = $this->view->translate('Webacula help');
        Zend_Loader::loadClass('Zend_Version');
        $this->view->zend_version = Zend_Version::VERSION;
        $this->view->db_adapter_bacula = Zend_Registry::get('DB_ADAPTER');
        $db = Zend_Registry::get('db_bacula');
        $this->view->db_server_version_bacula = $db->getServerVersion();
        
        Zend_Loader::loadClass('Version');
        $ver = new Version();
        $this->view->catalog_version_bacula = $ver->getVesion();

        Zend_Loader::loadClass('Director');
        $dir = new Director();
        $this->view->director_version = $dir->getDirectorVersion();
        $this->view->bconsole_version = $dir->getBconsoleVersion();
    }

    function myPhpInfoAction ()    {
    }


}