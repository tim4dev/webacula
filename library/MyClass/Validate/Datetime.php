<?php
/**
 *
 * Copyright 2007, 2008, 2010 Yuri Timofeev tim4dev@gmail.com
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

class MyClass_Validate_Datetime implements Zend_Validate_Interface
{
    /**
     * Array of validation failure messages
     *
     * @var array
     */
    protected $_messages = array();
    protected $translate;


    public function __construct()
    {
        $this->translate = Zend_Registry::get('translate');
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid date of the format YYYY-MM-DD
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $valueString = (string) $value;

        if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $valueString)) {
            $this->_messages[] = sprintf( $this->translate->_("'%s' is not of the format YYYY-MM-DD HH:MM:SS"), $valueString);
            return false;
        }

        list($year, $month, $day) = sscanf($valueString, '%d-%d-%d');

        if (!checkdate($month, $day, $year)) {
            $this->_messages[] = sprintf( $this->translate->_("'%s' does not appear to be a valid date"), $valueString);
            return false;
        }
        return true;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns array of validation failure messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    public function getErrors()
    {
        return $this->_messages;
    }

}
