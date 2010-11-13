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
    protected $translate;
    protected $bacula_acl;

    
    public function __construct()
    {
        $this->translate = Zend_Registry::get('translate');
        Zend_Loader::loadClass('Job');
        $this->bacula_acl = new MyClass_BaculaAcl();
    }

    
    public function isValid($value)    {
        if ( empty($value) )  // OK to restore to default "Where" location
            return TRUE;
        // do Bacula ACLs
        if ( $this->bacula_acl->doOneBaculaAcl($value, 'where') )
            return TRUE;
        else {
            $this->_messages[0] = sprintf( $this->translate->_("Bacula ACLs : access denied for Where '%s'"), $value);
            return FALSE;
        }
    }

    
    public function getMessages()
    {
        return $this->_messages;
    }

    public function getErrors()
    {
        return $this->_messages;
    }


}