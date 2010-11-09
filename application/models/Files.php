<?php
/**
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
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

class Files
{
   public $db;
   public $db_adapter;

    public function __construct()
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
    }

    public function getSelectFilesByJobId($jobid)
    {
        // do Bacula ACLs
        Zend_Loader::loadClass('Job');
		$table = new Job();
        if ( !$table->isJobIdExists($jobid) )
                return FALSE;
        // !!! IMPORTANT !!! с Zend Paginator нельзя использовать DISTINCT иначе не работает в PDO_PGSQL
        $select = new Zend_Db_Select($this->db);
        $select->from(array('f' => 'File'), array('FileId', 'FileIndex', 'LStat'));
        $select->joinLeft(array('p' => 'Path'), 'f.PathId = p.PathId' ,array('Path'));
        $select->joinLeft(array('n' => 'Filename'), 'f.FileNameId = n.FileNameId',array('Name'));
        $select->where("f.JobId = ?", $jobid);
        $select->order(array('f.FileIndex', 'f.FileId'));
        return $select;
    }


}