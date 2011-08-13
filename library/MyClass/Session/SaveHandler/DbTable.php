<?php
/**
 * Copyright 2011 Yuri Timofeev tim4dev@gmail.com
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

require_once 'Zend/Session/SaveHandler/DbTable.php';
require_once 'Zend/Auth.php';

class MyClass_Session_SaveHandler_DbTable  extends Zend_Session_SaveHandler_DbTable
{
    /**
     * Session table login column
     *
     * @var string
     */
    protected $_loginColumn = 'login';

    
    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        if( Zend_Auth::getInstance()->hasIdentity() ) {
            $ident = Zend_Auth::getInstance()->getIdentity();
            $login = $ident->login;
        }  else {
            $login = '';
        }

        $return = false;

        $data = array($this->_loginColumn => $login,
                      $this->_modifiedColumn => time(),
                      $this->_dataColumn     => (string) $data);

        // next original ZF code
        $rows = call_user_func_array(array(&$this, 'find'), $this->_getPrimary($id));

        if (count($rows)) {
            $data[$this->_lifetimeColumn] = $this->_getLifetime($rows->current());

            if ($this->update($data, $this->_getPrimary($id, self::PRIMARY_TYPE_WHERECLAUSE))) {
                $return = true;
            }
        } else {
            $data[$this->_lifetimeColumn] = $this->_lifetime;

            if ($this->insert(array_merge($this->_getPrimary($id, self::PRIMARY_TYPE_ASSOC), $data))) {
                $return = true;
            }
        }

        return $return;
    }


}
