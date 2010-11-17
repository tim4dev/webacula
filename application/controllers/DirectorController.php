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

class DirectorController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Director');
    }

    function statusdirAction()
    {
        $this->view->title = $this->view->translate->_("Status Director");
        // do Bacula ACLs
        $command = 'status';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
        	$msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
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
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }



    function listjobtotalsAction()
    {
        $this->view->title = $this->view->translate->_("List of Job Totals");
        // do Bacula ACLs
        $command = 'list';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
            $msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        $director = new Director();
        if ( !$director->isFoundBconsole() )	{
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }
        $astatusdir = $director->execDirector(
" <<EOF
list jobtotals
@quit
EOF"
        );
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] != 0 )	{
            $this->view->result_error = $astatusdir['result_error'];
        }
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }



}
