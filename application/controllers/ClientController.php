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
 */

/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ClientController extends Zend_Controller_Action
{

    function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Client');

        // for input field validation
        Zend_Loader::loadClass('Zend_Validate');
        Zend_Loader::loadClass('Zend_Filter_Input');
        Zend_Loader::loadClass('Zend_Validate_StringLength');
        Zend_Loader::loadClass('Zend_Validate_NotEmpty');
        Zend_Loader::loadClass('Zend_Validate_Digits');
        $validators = array(
            '*' => array(
                new Zend_Validate_StringLength(1, 255)
            ),
            'id'   => array(
                'Digits',
                'NotEmpty'
            ),
            'name' => array(
                'NotEmpty'
            )
        );
        $filters = array(
            '*'  => 'StringTrim',
            'id' => 'Digits'
        );
        $this->input = new Zend_Filter_Input($filters, $validators);

        $this->view->translate = Zend_Registry::get('translate');
	}

    function allAction()
    {
        $this->view->title = $this->view->translate->_("Clients");
        $clients = new Client();
        $order  = array('ClientId', 'Name');
        $this->view->clients = $clients->fetchAll(null, $order);
    }

    function statusClientIdAction()
    {
        // http://localhost/webacula/client/status-client-id/id/1/name/local.fd
        $this->input->setData( array('id' => $this->_getParam('id'), 'name' => $this->_getParam('name')) );
        if ( $this->input->isValid() ) {
            // unused ? $client_id   = $this->input->getEscaped('id');
            $client_name = $this->input->getEscaped('name');
        } else {
            $this->view->result = 'NOVALID';
            return;
        }
        $this->view->title = $this->view->translate->_("Client") . " " . $client_name;
        $config = Zend_Registry::get('config');

        // check access to bconsole

        if ( !file_exists($config->bacula->bconsole))	{
            $this->view->result = 'NOFOUND';
            return;
        }

        $command_output = '';
        $return_var = 0;
        $bconsolecmd = '';
        if ( isset($config->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->bacula->sudo . ' ' . $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->bacula->bconsole . ' ' . $config->bacula->bconsolecmd;
        }

exec($bconsolecmd . " <<EOF
status client=\"$client_name\"
quit
EOF", $command_output, $return_var);

        // check return status of the executed command
        if ( $return_var != 0 )	{
            $this->view->result = 'ERR';
            return;
        }

        $this->view->result = $command_output;
    }


}
