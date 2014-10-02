<?php
/**
 * Copyright 2007, 2008, 2009, 2011 Yuriy Timofeev tim4dev@gmail.com
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
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */


require_once 'Zend/Controller/Action.php';

class WbjobdescController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        Zend_Loader::loadClass('Wbjobdesc'); // load model
        Zend_Loader::loadClass('FormJobdesc');
    }


    public function indexAction()
    {
        $this->view->title = $this->view->translate->_("Job Descriptions");
        // get data from model
        $jobdesc = new wbJobDesc();
        $rows_jobdesc = $jobdesc->fetchAll(null, array('name_job', 'desc_id') );
        // check
        $result = array();
        Zend_Loader::loadClass('Job');
        $job = new Job();
        $i = 0;
        foreach( $rows_jobdesc as $row ) {
            $result[$i] = $row->toArray();
            if ( $job->isJobNameExists($row['name_job']) )
                $result[$i]['exists'] = 1;
            else
                $result[$i]['exists'] = 0;
            $i++;
        }
        $this->view->result = $result;
    }


    public function addAction()
    {
        $this->view->title = $this->view->translate->_("Add Job Description");
        $form = new formJobdesc();
        if ( $this->_request->isPost() &&  ($this->_request->getParam('form1') == '1') ) {
            if ( $form->isValid($this->_getAllParams()) ) {
                $name_job = stripslashes( trim( $this->_request->getParam('name_job') ) );
                $short_desc = stripslashes( trim( $this->_request->getParam('short_desc') ) );
                $description = stripslashes( trim( $this->_request->getParam('description') ) );
                $retention_period = stripslashes( trim( $this->_request->getParam('retention_period') ) );
                $table = new wbJobDesc();
                $data = array(
                    'name_job'    => $name_job,
                    'short_desc'  => $short_desc,
                    'description' => $description,
                    'retention_period' => $retention_period
                );

                try {
                    $rows_affected = $table->insert($data);
                    if ($rows_affected) {
                        $this->_helper->redirector('index');
                        return;
                    }
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                }
            }
        }
        $this->view->form = $form;
    }


    public function modifyAction()
    {
        $this->view->title = "Modify record";
        $form = new formJobdesc();
        if ( $this->_request->isPost() &&  ($this->_request->getParam('form1') == '1') ) {
            // get modified data
            if ( $form->isValid($this->_getAllParams()) ) {
                $desc_id  = intval( $this->_request->getParam('desc_id') );
                $name_job = stripslashes( trim( $this->_request->getParam('name_job') ) );
                $short_desc = stripslashes( trim( $this->_request->getParam('short_desc') ) );
                $description = stripslashes( trim( $this->_request->getParam('description') ) );
                $retention_period = stripslashes( trim( $this->_request->getParam('retention_period') ) );
                $table = new wbJobDesc();
                $data = array(
                    'name_job'    => $name_job,
                    'short_desc'  => $short_desc,
                    'description' => $description,
                    'retention_period' => $retention_period
                );
                $where = $table->getAdapter()->quoteInto('desc_id = ?', $desc_id);

                try {
                    $rows_affected = $table->update($data, $where);
                    if ($rows_affected) {
                        $this->_helper->redirector('index');
                        return;
                    }
                } catch (Zend_Exception $e) {
                    $this->view->exception = "<br>Caught exception: " . get_class($e) .
                        "<br>Message: " . $e->getMessage() . "<br>";
                    $this->view->form = $form;
                }
            }
        } else {
            // data not from form
            $desc_id    = intval( $this->_request->getParam('desc_id') );
            $this->view->title .= " #$desc_id";
            if ($desc_id) {
                // get data from table
                $table = new wbJobDesc();
                $row = $table->find($desc_id)->current();
                // fill form
                $form->populate( array(
                    'desc_id'     => $row->desc_id,
                    'name_job'    => $row->name_job,
                    'short_desc'  => $row->short_desc,
                    'description' => $row->description,
                    'retention_period' => $row->retention_period
                ));
            }
            $this->view->form = $form;
        }
    }


    public function deleteAction()
    {
        $desc_id = intval($this->_request->getParam('desc_id'));
        $table = new wbJobDesc();
        $where = $table->getAdapter()->quoteInto('desc_id = ?', $desc_id);
        $table->delete($where);
        $this->_helper->redirector('index');
    }



}
