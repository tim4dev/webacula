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
require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Submit.php';
require_once 'Zend/Form/Element/Select.php';

class FormJobrun extends Zend_Form
{
    
    public function myRemoveDecorators($el)
    {
        $el->removeDecorator('Label');   // remove tag <dt>
        $el->removeDecorator('HtmlTag'); // remove tag <dd>
    }

    public function init()
    {
        $translate = Zend_Registry::get('translate');
        Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // Set the method for the display form to POST
        $this->setMethod('post');
        $from_form = $this->addElement('hidden', 'from_form', array(
            'value' => '1'
        ));
        $this->myRemoveDecorators($from_form);
        // load models
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('FileSet');
        Zend_Loader::loadClass('Storage');
        /*
         *  Job Name
         */
        $table_job = new Job();
        $jobs = $table_job->getListJobs();
        // select
        $jobname = $this->createElement('select', 'jobname', array(
            'label'    => 'Job Name',
            'required' => true,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $this->myRemoveDecorators($jobname);
        foreach( $jobs as $v) {
            $jobname->addMultiOption($v, $v);
        }
        /*
         * Client 
         */ 
        $table_client = new Client();
        $order  = array('ClientId', 'Name');
        $clients = $table_client->fetchAll(null, $order);
        // select
        $client = $this->createElement('select', 'client', array(
            'label'    => 'Client',
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $this->myRemoveDecorators($client);
        $client->addMultiOption('', $translate->_("Default"));
        foreach( $clients as $v) {
            $client->addMultiOption($v->name, $v->name);
        }
        /* 
         * Fileset
         */
        $table_filesets = new Fileset();
        $order  = array('Fileset');
        $filesets = $table_filesets->fetchAll(null, $order);
        // select
        $fileset = $this->createElement('select', 'fileset', array(
            'label'    => 'Fileset',
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $this->myRemoveDecorators($fileset);
        $fileset->addMultiOption('', $translate->_("Default"));
        foreach( $filesets as $v) {
            $fileset->addMultiOption($v->fileset, $v->fileset);
        }
        /* 
         * Storage
         */
        $table_storages = new Storage();
        $order  = array('Name');
        $storages = $table_storages->fetchAll(null, $order);
        // select
        $storage = $this->createElement('select', 'storage', array(
            'label'    => 'Storage',
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $this->myRemoveDecorators($storage);
        $storage->addMultiOption('', $translate->_("Default"));
        foreach( $storages as $v) {
            $storage->addMultiOption($v->name, $v->name);
        }
        /* 
         * Level
         */
        // select
        $level = $this->createElement('select', 'level', array(
            'label'    => 'Level',
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 20em;'
        ));
        $this->myRemoveDecorators($level);
        $level->addMultiOptions(array(
            ''             => $translate->_("Default"),
            "Full"         => $translate->_("Full level"),
            "Incremental"  => $translate->_("Incremental level"),
            "Differential" => $translate->_("Differential level")
        ));
        /* 
         * Spool
         */
        // select
        $spool = $this->createElement('select', 'spool', array(
            'label'    => 'Spool',
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 10em;'
        ));
        $this->myRemoveDecorators($spool);
        $spool->addMultiOptions(array(
            ''    => $translate->_("Default"),
            "yes" => $translate->_("Yes"),
            "no"  => $translate->_("No")
        ));
        /*
         * checkbox Now
         */
        $checkbox_now = $this->createElement('checkbox', 'checkbox_now', array(
            'label'    => 'Now',
            'required' => false,
            'onclick'  => 'whenNow(this)',
            'checked'  => 1
        ));
        $this->myRemoveDecorators($checkbox_now);
        /*
         * When : date/time
         */
        // date
        $date_when = $this->createElement('text', 'date_when', array(
            'label'     => '',
            'required'  => false,
            'size'      => 10,
            'maxlength' => 10,
            'disabled'  =>'true',
            'value'     => date('Y-m-d', time())
        ));
        $date_when->addValidator('StringLength', false, array(10, 10) );
        $this->myRemoveDecorators($date_when);
        // time
        $time_when = $this->createElement('text', 'time_when', array(
            'label'     => '',
            'required'  => false,
            'size'      => 8,
            'maxlength' => 8,
            'disabled'  =>'true',
            'value'     => date('H:i:s', time())
        ));
        $time_when->addValidator('StringLength', false, array(8, 8) );
        $this->myRemoveDecorators($time_when);
        /*
         * submit button
         */ 
        $submit = new Zend_Form_Element_Submit('submit',array(
            'class' => 'prefer_btn',
            'label'=>'Run Job'
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $jobname,
            $client,
            $fileset,
            $storage,
            $level,
            $spool,
            $checkbox_now,
            $date_when,
            $time_when,
            $submit
        ));
    }
    
}
