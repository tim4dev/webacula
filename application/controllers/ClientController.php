<?php
/**
 *
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuri Timofeev tim4dev@gmail.com
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

class ClientController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Director');
	}

    function allAction()
    {
        $this->view->title = $this->view->translate->_("Clients");
        $clients = new Client();
        $order  = array('Name');
        $this->view->clients = $clients->fetchAll(null, $order);
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }


    function statusClientIdAction()
    {
        // http://localhost/webacula/client/status-client-id/id/1/name/local.fd
        $client_name = $this->_getParam('name');
        $this->view->title = $this->view->translate->_("Client") . " " . $client_name;
        // do Bacula ACLs
        $command = 'status';
        if ( !$this->bacula_acl->doOneBaculaAcl($command, 'command') ) {
            $msg = sprintf( $this->view->translate->_('You try to run Bacula Console with command "%s".'), $command );
            $this->_forward('bacula-access-denied', 'error', null, array('msg' => $msg ) ); // action, controller
            return;
        }
        $director = new Director();
        if ( !$director->isFoundBconsole() )    {
            $this->view->result_error = 'NOFOUND_BCONSOLE';
            $this->render();
            return;
        }
        $astatusdir = $director->execDirector(
" <<EOF
status client=\"$client_name\"
.
@quit
EOF"
        );
        $this->view->command_output = $astatusdir['command_output'];
        // check return status of the executed command
        if ( $astatusdir['return_var'] != 0 )   {
            $this->view->result_error = $astatusdir['result_error'];
        }
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }


}