<?php
/**
 *  *
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
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

require_once 'Zend/Validate/Interface.php';
require_once 'Zend/Validate.php';

class MyClass_Validate_BaculaJobId implements Zend_Validate_Interface
{
    protected $_messages = array();

    /**
     * See ZF API documentation
     * Returns true if and only if $value meets the validation requirements
     * If $value fails validation, then this method returns false, and $messages will contain an array
     * of messages that explain why the validation failed.
     */
    public function isValid($value)
    {
        $this->_messages = array();

        Zend_Loader::loadClass('Job');
    	$jobs = new Job();
    	$value = $jobs->getAdapter()->quote($value);
		$ret = $jobs->fetchRow("JobId = $value", null);

		if ( !$ret) {
			$this->_messages[] = "JobId $value is not found in Bacula database";
			return false;
		}
		return true;
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
