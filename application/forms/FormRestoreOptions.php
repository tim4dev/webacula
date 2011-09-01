<?php
/**
 * Copyright 2010, 2011 Yuri Timofeev tim4dev@gmail.com
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

class FormRestoreOptions extends Zend_Form
{

    protected $_action_cancel = '';
    protected $translate;
    protected $bacula_acl; // bacula acl

    public  $elDecorators = array(
        'ViewHelper',
        'Errors'
    );


    public function init()
    {       
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        /*
         * hidden fields
         */
        $from_form = $this->addElement('hidden', 'from_form', array(
            'decorators' => $this->elDecorators,
            'value' => '1'
        ));
        $type_restore = $this->addElement('hidden', 'type_restore', array(
            'decorators' => $this->elDecorators
        ));
        $jobid = $this->addElement('hidden', 'jobid', array(
            'decorators' => $this->elDecorators
        ));
        // for restore single file
        $fileid = $this->addElement('hidden', 'fileid', array(
            'decorators' => $this->elDecorators
        ));
        // load models
        Zend_Loader::loadClass('Client');
        Zend_Loader::loadClass('Storage');
        Zend_Loader::loadClass('Pool');
        Zend_Loader::loadClass('FileSet');
        // ACLs
        $this->bacula_acl = new MyClass_BaculaAcl();

        /******* standard options ******/
        /*
         * client_name / client
         * client_name_to / restoreclient
         *
         */
        $table_client = new Client();
        $order  = array('Name');
        $clients = $table_client->fetchAll(null, $order);
        $client_name = $this->createElement('select', 'client_name', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Client'),
            'required' => true,
            'class'    => 'ui-select',
            'style'    => 'width: 18em;'
        ));
        $client_name_to = $this->createElement('select', 'client_name_to', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Restore client'),
            'required' => false,
            'class'    => 'ui-select',
            'style'    => 'width: 18em;'
        ));
        $client_name->addMultiOption('', $this->translate->_("Default"));
        $client_name_to->addMultiOption('', $this->translate->_("Default"));
        foreach( $clients as $v) {
            $client_name->addMultiOption($v['name'], $v['name']);
            $client_name_to->addMultiOption($v['name'], $v['name']);
        }
        /*
         * pool
         */
        $table_pool = new Pool();
        $order  = array('Name', 'PoolId');
   	    $pools = $table_pool->fetchAll(null, $order);
        $pool = $this->createElement('select', 'pool', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Pool'),
            'required' => false,
            'class'    => 'ui-select',
            'style'    => 'width: 18em;'
        ));
        $pool->addMultiOption('', $this->translate->_("Default"));
        foreach( $pools as $v) {
            $pool->addMultiOption($v['name'], $v['name']);
        }
        /*
         * fileset
         */
        $table_fileset = new FileSet();
        $order  = array('Fileset');
   	    $filesets = $table_fileset->fetchAll(null, $order);
        $fileset = $this->createElement('select', 'fileset', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Fileset'),
            'required' => false,
            'class'    => 'ui-select',
            'style'    => 'width: 18em;'
        ));
        $fileset->addMultiOption('', $this->translate->_("Default"));
        foreach( $filesets as $v) {
            $fileset->addMultiOption($v['fileset'], $v['fileset']);
        }
        /*
         * storage
         */
        $table_storage = new Storage();
        $order  = array('Name');
   	    $storages = $table_storage->fetchAll(null, $order);
        $storage = $this->createElement('select', 'storage', array(
            'decorators' => $this->elDecorators,
            'label'    => $this->translate->_('Storage'),
            'required' => false,
            'class'    => 'ui-select',
            'style'    => 'width: 18em;'
        ));
        $storage->addMultiOption('', $this->translate->_("Default"));
        foreach( $storages as $v) {
            $storage->addMultiOption($v['name'], $v['name']);
        }
        /*
         * restore_job_select
         * if have multiple Restore Job resources
         */
        $config = Zend_Registry::get('config');
        if ( $config->general->bacula_restore_job )  {
            $restore_job_select = $this->createElement('select', 'restore_job_select', array(
                'decorators' => $this->elDecorators,
                'label'    => $this->translate->_('Restore Job Resource'),
                'required' => true,
                'class'    => 'ui-select',
                'style'    => 'width: 18em;'
            ));
            $bacula_restore_jobs = $config->general->bacula_restore_job->toArray();
            $restore_job_select->addMultiOption('', $this->translate->_("Default"));
            $i = 1;
            foreach( $bacula_restore_jobs as $v) {
                if ($this->bacula_acl->doOneBaculaAcl($v, 'job'))
                    $restore_job_select->addMultiOption($i++, $v);
            }
            // add element to form
            $this->addElement($restore_job_select);
        }

        /******* advanced options ******/
        /*
         * where
         */
        $where = $this->createElement('text', 'where', array(
            'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Where'),
            'required'  => false,
            'size'      => 50,
            'maxlength' => 255,
            'value'     => ''
        ));
        $where->addValidator('StringLength', false, array(0, 255) );
        /*
         * strip_prefix
         */
        $strip_prefix = $this->createElement('text', 'strip_prefix', array(
            'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Strip&nbsp;prefix'),
            'required'  => false,
            'size'      => 24,
            'maxlength' => 64,
            'value'     => ''
        ));
        $strip_prefix->addValidator('StringLength', false, array(0, 64) );
        /*
         * add_prefix
         */
        $add_prefix = $this->createElement('text', 'add_prefix', array(
            'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Add&nbsp;prefix'),
            'required'  => false,
            'size'      => 24,
            'maxlength' => 64,
            'value'     => ''
        ));
        $add_prefix->addValidator('StringLength', false, array(0, 64) );
        /*
         * add_suffix
         */
        $add_suffix = $this->createElement('text', 'add_suffix', array(
            'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Add&nbsp;suffix'),
            'required'  => false,
            'size'      => 24,
            'maxlength' => 64,
            'value'     => '' // '.old' // for debug
        ));
        $add_suffix->addValidator('StringLength', false, array(0, 64) );
        /*
         * regexwhere
         */
        $regexwhere = $this->createElement('text', 'regexwhere', array(
            'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('RegexWhere'),
            'required'  => false,
            'size'      => 24,
            'maxlength' => 64,
            'value'     => '' // '/test/qwerty/,/webacula/zzzasdf/'  // for debug
        ));
        $regexwhere->addValidator('StringLength', false, array(0, 64) );
        /*
         * submit button
         */
        $submit_button = new Zend_Form_Element_Submit('submit_button', array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok1',
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Run Restore Job')
        ));
        /*
         * cancel button
         */
        $cancel_button = new Zend_Form_Element_Submit('cancel_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'cancel1',
            'label' => $this->translate->_('Cancel')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $client_name,
            $client_name_to,
            $pool,
            $fileset,
            $storage,
            $where,
            $strip_prefix,
            $add_prefix,
            $add_suffix,
            $regexwhere,
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