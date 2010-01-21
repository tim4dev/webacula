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

    public function init()
    {
        $translate = Zend_Registry::get('translate');
        Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // Set the method for the display form to POST
        $this->setMethod('post');
        $from_form = $this->addElement('hidden', 'from_form', array(
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
            'label'    => 'Job Name',
            'required' => true,
            'class' => 'ui-select',
            'style' => 'width: 25em;'
        ));
        $jobname->removeDecorator('Label');
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
        $client->removeDecorator('Label');
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
        $fileset->removeDecorator('Label');
        $fileset->addMultiOption('', $translate->_("Default"));
        foreach( $filesets as $v) {
            $fileset->addMultiOption($v->fileset, $v->fileset);
        }
        
        
        
        
        
        
        // submit button
        $submit = new Zend_Form_Element_Submit('submit',array(
            'class' => 'prefer_btn',
            'label'=>'Run Job'
        ));
        // add elements to form
        $this->addElements( array(
            $jobname,
            $client,
            $fileset,
            $submit
        ));
    }
    
}
