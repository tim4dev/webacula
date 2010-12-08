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
 * Class for Logbook
 *
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
/*
 * workaround http://zendframework.com/issues/browse/ZF-7070
 * http://www.zfforums.com/zend-framework-components-13/databases-20/problem-postgres-2151.html#post11910
 */
class Wblogtype extends Zend_Db_Table
{
    public $db;
    public $db_adapter;
    protected $_name = 'webacula_logtype';
    protected $_primary = 'typeid';


    public function __construct ($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $config['db'] = $this->db;
        $config['sequence'] = true;
        parent::__construct($config);
    }


}
