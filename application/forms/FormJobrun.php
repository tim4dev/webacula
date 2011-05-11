<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuri Timofeev tim4dev@gmail.com
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

class FormJobrun extends Zend_Form
{

    protected $translate;



    public  $elDecorators = array(
        'ViewHelper',
        'Errors'
    );



    public function init()
    {
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // Set the method for the display form to POST
        $this->setMethod('post');
        $from_form = $this->addElement('hidden', 'from_form', array(
            'decorators' => $this->elDecorators,
            'value' => '1'
        ));
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
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Job Name'),
            'required' => true,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        foreach( $jobs as $v) {
            $jobname->addMultiOption($v, $v);
        }
        /*
         * Client
         */
        $table_client = new Client();
        //$order  = array('ClientId', 'Name');
        $order  = array('Name');
        $clients = $table_client->fetchAll(null, $order);
        // select
        $client = $this->createElement('select', 'client', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Client'),
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $client->addMultiOption('', $this->translate->_("Default"));
        foreach( $clients as $v) {
            $client->addMultiOption($v['name'], $v['name']);
        }
        /*
         * Fileset
         */
        $table_filesets = new Fileset();
        $order  = array('Fileset');
        $filesets = $table_filesets->fetchAll(null, $order);
        // select
        $fileset = $this->createElement('select', 'fileset', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Fileset'),
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $fileset->addMultiOption('', $this->translate->_("Default"));
        foreach( $filesets as $v) {
            $fileset->addMultiOption($v['fileset'], $v['fileset']);
        }
        /*
         * Storage
         */
        $table_storages = new Storage();
        $order  = array('Name');
        $storages = $table_storages->fetchAll(null, $order);
        // select
        $storage = $this->createElement('select', 'storage', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Storage'),
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $storage->addMultiOption('', $this->translate->_("Default"));
        foreach( $storages as $v) {
            $storage->addMultiOption($v['name'], $v['name']);
        }
        /*
         * Level
         */
        // select
        $level = $this->createElement('select', 'level', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Level'),
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 20em;'
        ));
        $level->addMultiOptions(array(
            ''             => $this->translate->_("Default"),
            "Full"         => $this->translate->_("Full level"),
            "Incremental"  => $this->translate->_("Incremental level"),
            "Differential" => $this->translate->_("Differential level")
        ));
        /*
         * Spool
         */
        // select
        $spool = $this->createElement('select', 'spool', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Spool'),
            'required' => false,
            'class' => 'ui-select',
            'style' => 'width: 15em;'
        ));
        $spool->addMultiOptions(array(
            ''    => $this->translate->_("Default"),
            "yes" => $this->translate->_("Yes"),
            "no"  => $this->translate->_("No")
        ));
        /*
         * checkbox Now
         */
        $checkbox_now = $this->createElement('checkbox', 'checkbox_now', array(
            'decorators' => $this->elDecorators,
            'label'    => 'Now',
            'onclick'  => 'whenNow(this)',
            'checked'  => 1
        ));
        /*
         * When : date/time
         */
        // date
        $date_when = $this->createElement('text', 'date_when', array(
            'decorators' => $this->elDecorators,
            'label'     => '',
            'required'  => false,
            'size'      => 10,
            'maxlength' => 10,
            'disabled'  =>'true',
            'value'     => date('Y-m-d', time())
        ));
        $date_when->addValidator('StringLength', false, array(1, 10) );
        // time
        $time_when = $this->createElement('text', 'time_when', array(
            'decorators' => $this->elDecorators,
            'label'     => '',
            'required'  => false,
            'size'      => 8,
            'maxlength' => 8,
            'disabled'  =>'true',
            'value'     => date('H:i:s', time())
        ));
        $time_when->addValidator('StringLength', false, array(1, 8) );
        /*
         * submit button
         */
        $submit = new Zend_Form_Element_Submit('submit',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok1',
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Run Job')
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
