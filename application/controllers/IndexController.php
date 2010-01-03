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

class IndexController extends MyClass_ControllerAction
{

    function init ()
    {
        parent::init();
        // load model
        Zend_Loader::loadClass('Job');
    }

    function indexAction ()
    {
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->setLayout('dashboard');
        }
        // actionToStack($action, $controller, $module, $params);
        $this->_helper->actionStack('problem-dashboard', 'job');
        $this->_helper->actionStack('problem-dashboard', 'volume');
        $this->_helper->actionStack('next-dashboard', 'job');
        $this->_helper->actionStack('running-dashboard', 'job');
        $this->_helper->actionStack('terminated-dashboard', 'job');
        $config = Zend_Registry::get('config');
        if (empty($config->head_title)) {
            $this->view->titleDashboard = "webacula Main Page";
        } else {
            $this->view->titleDashboard = $config->head_title;
        }
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

}
