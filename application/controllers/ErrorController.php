<?php
/**
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

/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ErrorController extends Zend_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->view->translate = Zend_Registry::get('translate');
    }



    public function errorAction()
    {
        Zend_Loader::loadClass('Zend_Version');
        $this->view->zend_version = Zend_Version::VERSION;
        $this->view->db_adapter_bacula   = Zend_Registry::get('DB_ADAPTER');
        $db = Zend_Registry::get('db_bacula');
        $this->view->db_server_version_bacula = $db->getServerVersion();

        $ver = new Version();
        $this->view->catalog_version_bacula = $ver->getVesion();
        Zend_Loader::loadClass('Director');
        $dir = new Director();
        $this->view->director_version = $dir->getDirectorVersion();
        $this->view->bconsole_version = $dir->getBconsoleVersion();

        $errors = $this->_getParam('error_handler');
        if ($errors) {
            $exception = $errors->exception;
            $this->view->err_message = $exception->getMessage();
            $this->view->err_trace   = $exception->getTraceAsString();
            switch ($errors->type) {
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                    // ошибка 404 - не найден контроллер или действие
                    $this->getResponse()->setHttpResponseCode(404);
                    //.получение данных для отображения...
                    $this->view->err_custom_message = 'Webacula : Error 404. Page Not Found.';
                    break;
                default:
                    // ошибка приложения
                    $this->getResponse()->setHttpResponseCode(500);
                    $this->view->message = 'Webacula : application error.';
                    break;
            } // switch
        } else {
            $this->getResponse()->setHttpResponseCode(500);
            $this->view->err_message = 'Webacula : application error.';
            $this->view->err_trace   = __METHOD__ . ' line ' . __LINE__;
        }
    }



    public function webaculaAccessDeniedAction() {
        // show "Webacula : access denied."
        $this->view->msg = $this->_getParam('msg');
    }


    public function baculaAccessDeniedAction() {
        // show "Access denied."
        $this->view->msg = $this->_getParam('msg');
    }


}