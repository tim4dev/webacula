<?php
/**
 * Copyright 2007, 2008, 2009, 2011 Yuri Timofeev tim4dev@gmail.com
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

class Media extends Zend_Db_Table
{
    public $db;
    public $db_adapter;
    protected $bacula_acl; // bacula acl

    public function __construct($config = array())
    {
        $this->db         = Zend_Registry::get('db_bacula');
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->bacula_acl = new MyClass_BaculaAcl();
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
    function getProblemVolumes($order=null)
    {
        $db = Zend_Registry::get('db_bacula');
        // make select from multiple tables
        $select = new Zend_Db_Select($db);
        $select->distinct();
        $select->from(array('m' => 'Media'),
            array("MediaId", 'PoolId', 'StorageId',
            'VolumeName', 'VolStatus', 'VolBytes', 'MaxVolBytes', 'VolJobs', 'VolRetention', 'Recycle', 'Slot',
            'InChanger', 'MediaType', 'FirstWritten', 'LastWritten'
            ));
        $select->joinLeft(array('p' => 'Pool'), 'm.PoolId = p.PoolId', array('PoolName' => 'p.Name'));
        $select->where("VolStatus IN ('Error', 'Disabled')");
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $result = $select->query()->fetchAll(null, $order);
        // do Bacula ACLs
        return $this->bacula_acl->doBaculaAcl($result, 'poolname', 'pool');
    }


    function getByName($volname, $order='VolumeName')
    {
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

    function detail($media_id)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from('Media',
            array('MediaId', 'PoolId', 'StorageId',
            'VolumeName', 'VolStatus', 'VolBytes', 'MaxVolBytes', 'VolJobs', 'VolRetention',
            'Recycle', 'Slot', 'InChanger', 'MediaType',
            'FirstWritten', 'LastWritten',
            'LabelDate', 'VolFiles', 'VolBlocks', 'VolMounts',
            'VolParts', 'VolErrors', 'VolWrites', 'VolCapacityBytes', 'Enabled',
            'ActionOnPurge', 'VolUseDuration', 'MaxVolJobs', 'MaxVolFiles',
            'VolReadTime', 'VolWriteTime', 'EndFile', 'EndBlock',
            'RecycleCount', 'InitialWrite','Comment'
        ));
        $select->where('MediaId = ?', $media_id);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>";exit; // for !!!debug!!!
        $stmt = $select->query();
        return $stmt->fetchAll();
    }

    function getById($pool_id, $order)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from('Media',
            array('MediaId', 'PoolId', 'StorageId',
            'VolumeName', 'VolStatus', 'VolBytes', 'MaxVolBytes', 'VolJobs', 'VolRetention', 'Recycle', 'Slot',
            'InChanger', 'MediaType',
            'FirstWritten',	'LastWritten'
        ));
        $select->where('PoolId = ?', $pool_id);
        $select->order($order);
        //$sql = $select->__toString(); echo "<pre>$sql</pre>";exit; // for !!!debug!!!
        $stmt = $select->query();
        return $stmt->fetchAll();
    }

    public function getPoolId($media_id)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from('Media', array('PoolId'));
        $select->where('MediaId = ?', $media_id);
        // fetch one row
        $row = $this->_db->fetchRow($select);
        return $row['poolid'];
    }

    public function getVolumeCountByPool($pool_id) {
        $select = new Zend_Db_Select($this->db);
        $select->from('Media', array('COUNT(MediaId) as count'));
        $select->where('PoolId = ?', $pool_id);
        // fetch one row
        $row = $this->_db->fetchRow($select);
        return $row['count'];
    }

    /**
     * List Volumes likely to need replacement from age or errors
     * bconsole -> query -> 16
     * @return rows
     */
    function getVolumesNeedReplacement()
    {
        $db = Zend_Registry::get('db_bacula');
        // make select from multiple tables
        $select = new Zend_Db_Select($db);
        $select->from('Media',
            array("MediaId", 'PoolId', 'StorageId',
            'VolumeName', 'VolStatus', 'VolJobs', 'MediaType', 'VolMounts', 'VolErrors', 'VolWrites', 'VolBytes'
            ));
        $select->orWhere("VolErrors > 0");
        $select->orWhere("VolStatus = 'Error'");
        $select->orWhere("VolMounts > 50"); // Number of time media mounted
        $select->orWhere("VolStatus = 'Disabled'");
        $select->orWhere("VolWrites > 3999999");  // Number of writes to media
        $select->order( array('VolStatus ASC', 'VolErrors', 'VolMounts', 'VolumeName DESC') );
        //$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!
        $stmt = $select->query();
        return $stmt->fetchAll();
    }

}