<?php
/**
 *
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

class BconsoleController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Director');
    }

    function wterminalAction()
    {
    }


    function cmdAction()
    {
        $bcommand = addslashes( trim( $this->_request->getPost('bcommand') ));
        $this->view->bcommand = $bcommand;
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
        list($cmd) = explode(' ', $bcommand);
        // do Bacula ACLs
        if ( !$this->bacula_acl->doOneBaculaAcl($cmd, 'command') ) {
            $this->view->msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $cmd ) .
                '<br><b>Bacula ACLs : '. $this->view->translate->_('Access denied.') . '</b><br>';
            $this->render();
            return;
        }
        $director = new Director();
        if ( !$director->isFoundBconsole() )    {
            $this->view->msg = '<b>'. $this->view->translate->_('ERROR: bconsole not found.'). '</b><br>';
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
    }


}