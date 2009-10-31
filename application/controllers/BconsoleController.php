<?php
/**
 *
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

class BconsoleController extends MyClass_ControllerAction
{

    protected $aNotAvailCmd = array('delete', 'prune', 'purge', 'python', 'setip', 'sqlquery', 'query', 'wait');

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Director');
    }

    function wterminalAction()
    {
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
    }


    function cmdAction()
    {
        $bcommand = addslashes( trim( $this->_request->getPost('bcommand') ));
        $this->view->bcommand = $bcommand;
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
        list($cmd) = explode(' ', $bcommand, 1);
        if ( in_array($cmd, $this->aNotAvailCmd) ) {
            $this->view->result_error = 'CMD_NOT_AVAIL';
            $this->render();
            return;
        }
        $director = new Director();
        if ( !$director->isFoundBconsole() )    {
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }
        $astatusdir = $director->execDirector(
"<<EOF
$bcommand
@quit
EOF"
        );
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        $this->view->return_var = $astatusdir['return_var'];
        if ( $astatusdir['return_var'] != 0 )   {
            $this->view->result_error = $astatusdir['result_error'];
        }
    }


}
