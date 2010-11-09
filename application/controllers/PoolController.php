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


require_once 'Zend/Controller/Action.php';

class PoolController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Pool');
    }

   function allAction()
   {
      $order  = addslashes(trim( $this->_request->getParam('order', 'Name') ));
      $this->view->title = $this->view->translate->_("Pools");
      // to view
      $this->view->meta_refresh = 300; // meta http-equiv="refresh"
      $pools = new Pool();
      $this->view->pools = $pools->fetchAll(null, $order);
   }


}