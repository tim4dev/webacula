<?php
/**
 *  
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
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
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

require_once 'Zend/Validate/Interface.php';
require_once 'Zend/Validate.php';

class MyClass_Validate_BaculaAclWhere implements Zend_Validate_Interface
{
    protected $_messages = array();

    const ACCESS_DENIED = 'AccessDenied';


    public function __construct()
    {
        Zend_Loader::loadClass('Job');
        $translate = Zend_Registry::get('translate');
        Zend_Validate_Abstract::setDefaultTranslator($translate);
        $this->_messageTemplates = array(
            self::ACCESS_DENIED => $this->translate->_("Bacula ACLs : Where '%value%' access denied.")
        );
    }

    
    public function isValid($value)
    {
        $this->_error(self::ACCESS_DENIED);
        return false;
		//return true;
    }

    
    /**
     * See ZF API documentation
     * Returns an array of messages that explain why a previous isValid() call returned false.
     * If isValid() was never called or if the most recent isValid() call returned true, then this method returns an empty array.
     *
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * See ZF API documentation
     * Returns an array of errors that explain why a previous isValid() call returned false.
     * If isValid() was never called or if the most recent isValid() call returned true, then this method returns an empty array.
     *
     */
    public function getErrors()
    {
        return $this->_messages;
    }


}
