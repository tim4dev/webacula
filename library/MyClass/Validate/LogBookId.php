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

class MyClass_Validate_LogBookId implements Zend_Validate_Interface
{
    protected $_messages = array();

    public function __construct()
    {
        Zend_Loader::loadClass('Wblogbook');
    }

    public function isValid($logTxt)
    {
        $this->_messages = array();
        $matches = null;
        $pattern1 = '/LOGBOOK_ID=[\w]+([\s]+|$)/';
        $num1 = preg_match_all($pattern1, $logTxt, $matches);
        if ($num1) {
            // match LOGBOOK_ID
            $pattern2 = "/LOGBOOK_ID=/";
            foreach ($matches[0] as $value) {
                $ids = preg_split($pattern2, $value);
                $id = trim($ids[1]);

                $jobs = new Job();
                $ret= $jobs->getByJobId($id);
                if ( !$ret) {
                    $this->_messages[] = "Logbook $id is not found in Webacula database";
                    return false;
                }
            }
        }
        return true;
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
