<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011 Yuriy Timofeev tim4dev@gmail.com
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
require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Submit.php';

class FormJobrun extends Zend_Form
{

    protected $translate;
	 protected $_action_cancel = '';



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
        Zend_Loader::loadClass('Pool');
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
            'class' => 'form-control'
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
            'class' => 'form-control'
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
            'class' => 'form-control'
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
            'class' => 'form-control'
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
            'class' => 'form-control'
        ));
        $level->addMultiOptions(array(
            ''             => $this->translate->_("Default"),
            "Full"         => $this->translate->_("Full level"),
            "Incremental"  => $this->translate->_("Incremental level"),
            "Differential" => $this->translate->_("Differential level")
        ));
        /*
         * Pool
         */
        $table_pool = new Pool();
        $order  = array('Name');
        $pools = $table_pool->fetchAll(null, $order);
        // select
        $pool = $this->createElement('select', 'pool', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Pool'),
            'required' => false,
            'class' => 'form-control'
        ));
        $pool->addMultiOption('', $this->translate->_("Default"));
        foreach( $pools as $v) {
            $pool->addMultiOption($v['name'], $v['name']);
        }
        /*
         * Spool
         */
        // select
        $spool = $this->createElement('select', 'spool', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Spool'),
            'required' => false,
            'class' => 'form-control'
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
            'class' => 'form-check-input',
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
            'disabled'  => 'true',
            'class'     => 'form-control',
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
            'class'     => 'form-control',
            'value'     => date('H:i:s', time())
        ));
        $time_when->addValidator('StringLength', false, array(1, 8) );
        /*
         * submit button
         */
        $submit_button = new Zend_Form_Element_Submit('submit_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok1',
            'class' => 'btn  btn-default',
            'label' => $this->translate->_('Run Job')
        ));
		  
        /*
         * cancel button
         */
        $cancel_button = new Zend_Form_Element_Submit('cancel_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'reset_'.__CLASS__,
            'class' => 'btn btn-default',
            'label' => $this->translate->_('Cancel')
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
            $pool,
            $spool,
            $checkbox_now,
            $date_when,
            $time_when,
            $submit_button,
				$cancel_button
        ));
    }
	 
    public function setActionCancel($url = '')
    {
        $this->_action_cancel = $url;
    }



    public function getActionCancel()
    {
        return $this->_action_cancel;
    }
	 

}
