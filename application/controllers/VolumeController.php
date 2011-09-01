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

require_once 'Zend/Controller/Action.php';

class VolumeController extends MyClass_ControllerAclAction
{

    function init ()
    {
        parent::init();
        // load model
        Zend_Loader::loadClass('Media');
        Zend_Loader::loadClass('Pool');
        Zend_Loader::loadClass('MyClass_SendEmail');
    }

    function findNameAction ()
    {
        // http://localhost/webacula/volume/find-name/volname/pool.file.7d.0005
        $volname = addslashes($this->_request->getParam('volname'));
        $order = addslashes(trim($this->_request->getParam('order', 'VolumeName')));
        $this->view->volname = $volname;
        if ($volname) {
            $this->view->title = $this->view->translate->_("Volume") . " " . $volname;
            $media = new Media();
            $this->view->result = $media->getByName($volname, $order);
        } else
            $this->view->result = null;
    }

    function findPoolIdAction ()
    {
        // http://localhost/webacula/volume/find-pool-id/id/2/name/pool.file.7d
        $pool_id = intval($this->_request->getParam('id'));
        $pool_name = addslashes($this->_request->getParam('name'));
        $order = addslashes(trim($this->_request->getParam('order', 'VolumeName')));
        $this->view->pool_id = $pool_id;
        $this->view->pool_name = $pool_name;
        if ($pool_id) {
            $this->view->title = $this->view->translate->_("Pool") . " " . $pool_name;
            $media = new Media();
            $this->view->result = $media->getById($pool_id, $order);
        } else
            $this->view->result = null;
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

    /**
     * Volumes with errors/problems
     */
    function problemAction ()
    {
        $this->view->titleProblemVolumes = $this->view->translate->_("Volumes with errors");
        $this->view->titleVolumesNeedReplacement = $this->view->translate->_("List Volumes likely to need replacement from age or errors");
        $order = addslashes(trim($this->_request->getParam('order', 'VolumeName')));
        // get data from model
        $media = new Media();
        $this->view->resultProblemVolumes = $media->getProblemVolumes($order);
        // fix http://sourceforge.net/apps/trac/webacula/ticket/81
        //$this->view->resultVolumesNeedReplacement = $media->getVolumesNeedReplacement();
        $this->view->meta_refresh = 300; // meta http-equiv="refresh"
    }

    /**
     * Volumes with errors/problems
     */
    function problemDashboardAction ()
    {
        if ($this->_helper->hasHelper('layout'))
            $this->_helper->layout->setLayout('dashboard');
        $this->view->titleProblemVolumes = $this->view->translate->_("Volumes with errors");
        $order = addslashes(trim($this->_request->getParam('order', 'VolumeName')));
        // get data from model
        $media = new Media();
        $this->view->resultProblemVolumes = $media->getProblemVolumes($order);
        if ( empty($this->view->resultProblemVolumes) ) {
            $this->_helper->viewRenderer->setNoRender();
        } else {
            $this->_helper->viewRenderer->setResponseSegment('volume_problem');
        }
    }

    /*
     * Volume detail info
     */
    function detailAction ()
    {
        // http://localhost/webacula/volume/detail/mediaid/2/
        $media_id = intval($this->_request->getParam('mediaid'));
        if ($media_id) {
            $this->view->title = $this->view->translate->_("Detail Volume") . " " . $media_id;
            $media = new Media();
            $this->view->result = $media->detail($media_id);
            $pools = new Pool();
            $this->view->pools = $pools->fetchAll();
        } else
            $this->view->result = null;
    }

    function updateAction ()
    {
        // ****************************** UPDATE record **********************************
        $media_id = trim($this->_request->getPost('mediaid'));
        $pool_id = trim($this->_request->getPost('poolid'));
        $volume_name = trim($this->_request->getPost('volumename'));
        if (! empty($media_id) && ! empty($pool_id)) {
            $media = new Media();
            // remember old value of poolid for this mediaid
            $old_pool_id = $media->getPoolId($media_id);
            // update Media record
            $data = array(
                'poolid'        => $pool_id ,
                'volstatus'     => trim($this->_request->getPost('volstatus')) ,
                'volretention'  => (int) trim($this->_request->getPost('volretention')) * 86400 ,
                'voluseduration'=> (int) trim($this->_request->getPost('voluseduration')),
                'recycle'       => trim($this->_request->getPost('recycle')) ,
                'slot'          => trim($this->_request->getPost('slot')) ,
                'inchanger'     => trim($this->_request->getPost('inchanger')) ,
                'maxvoljobs'    => trim($this->_request->getPost('maxvoljobs')) ,
                'maxvolfiles'   => trim($this->_request->getPost('maxvolfiles')) ,
                'comment'       => trim($this->_request->getPost('comment'))
            );
            $where = $media->getAdapter()->quoteInto('MediaId = ?', $media_id);
            $res = $media->update($data, $where);
            // if Volume moved to another Pool
            if ($old_pool_id != $pool_id) {
                // to count Volume count on both Pools
                $old_volume_count = $media->getVolumeCountByPool($old_pool_id);
                $volume_count = $media->getVolumeCountByPool($pool_id);
                $pool = new Pool();
                // update old Pool record
                $data = array('numvols' => $old_volume_count);
                $where = $pool->getAdapter()->quoteInto('PoolId = ?', $old_pool_id);
                $res = $pool->update($data, $where);
                // update new Pool record
                $data = array('numvols' => $volume_count);
                $where = $pool->getAdapter()->quoteInto('PoolId = ?', $pool_id);
                $res = $pool->update($data, $where);
            }
            // send email
            if ($res) {
                $email = new MyClass_SendEmail();
                // $from_email, $from_name, $to_email, $to_name, $subj, $body
                $email->mySendEmail(
                        $this->view->config->webacula->email->from,
                        'Webacula Volume controller',
                        $this->view->config->webacula->email->to_admin,
                        'Webacula admin',
                        $this->view->translate->_('Webacula : Updated Volume parameters'),
                        $this->view->translate->_('Media Id') . ': ' . $media_id . "\n" .
                            $this->view->translate->_('Volume Name') . ': ' . $volume_name . "\n\n" );
            }
        }
        $this->_redirect("/volume/detail/mediaid/$media_id");
        return;
    }


}