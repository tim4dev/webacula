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

class Volume extends Zend_Db_Table
{
	public $db;
	public $db_adapter;

	public function __construct($config = array())
	{
   		$this->db         = Zend_Registry::get('db_bacula');
		$this->db_adapter = Zend_Registry::get('DB_ADAPTER');
		parent::__construct($config);
	}


   protected function _setupTableName()
    {
        switch ($this->db_adapter) {
        case 'PDO_PGSQL':
            $this->_name = 'media';
            break;
		default: // including mysql, sqlite
			$this->_name = 'Media';
        }
        parent::_setupTableName();
    }

    protected function _setupPrimaryKey()
    {
        $this->_primary = 'mediaid';
        parent::_setupPrimaryKey();
    }

	/**
     * Get info Volumes with Status of media: Disabled, Error
     *
     */
    function GetProblemVolumes()
    {
    	$db = Zend_Registry::get('db_bacula');
    	// make select from multiple tables
    	$select = new Zend_Db_Select($db);

    	$select->distinct();

    	// Колонка Media.Enabled появилась только в версии 2.0.0
    	$select->from('Media',
	    	array("MediaId", 'PoolId', 'StorageId',
			'VolumeName', 'VolStatus', 'VolBytes', 'MaxVolBytes', 'VolJobs', 'VolRetention', 'Recycle', 'Slot',
			'InChanger', 'MediaType', 'FirstWritten', 'LastWritten'
			));
		$select->where("VolStatus IN ('Error', 'Disabled')");
		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
    	$result = $select->query();
		return $result;
    }
    
    
    function getByName($volname, $order)
    {
   		// Колонка Enabled появилась только в версии 2.0.0
   		$select = new Zend_Db_Select($this->db);
    	$select->from('Media',
	    	array('MediaId', 'PoolId', 'StorageId',
			'VolumeName', 'VolStatus', 'VolBytes', 'MaxVolBytes', 'VolJobs', 'VolRetention', 'Recycle', 'Slot',
			'InChanger', 'MediaType', 'FirstWritten', 'LastWritten'
		));
		$select->where('VolumeName = ?', $volname);
		$select->order($order);
		//$sql = $select->__toString(); echo "<pre>$sql</pre>"; // for !!!debug!!!
    	$stmt = $select->query();
		return $stmt->fetchAll();
    }

}