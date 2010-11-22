<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
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


class FormRole extends Zend_Form
{

    protected $translate;
    protected $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'


    public function init()
    {       
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        // decorator
        $this->setDecorators(array(
            array('ViewScript', array('viewScript' => 'form-role.phtml'))
        ));
        /*
         * hidden fields
         */
        $role_id = $this->addElement('hidden', 'role_id', array(
            'decorators' => $this->elDecorators
        ));
        /*
         * Webacula resources
         */
        Zend_Loader::loadClass('WbDtResources');
        $table = new WbDtResources();
        $wbDtResources = $table->fetchAll(null, 'id');
        // create element
        $webacula_resources = $this->createElement('multiselect', 'webacula_resources', array(
            'label'    => $this->translate->_('Webacula resources'),
            'class' => 'ui-select',
            'size' => 10
        ));
        foreach( $wbDtResources as $v ) {
            $webacula_resources->addMultiOptions(array( $v->id => $v->name . ' => ' . $v->description ));
        }
        unset ($table);
        /*
         * Bacula ACLs
         */
        /*
         * StorageACL
         */
        Zend_Loader::loadClass('WbStorageACL');
        $table = new WbStorageACL();
        $wbStorageACL = $table->fetchAll(null, 'id');
        // create element
        $storage_acl = $this->createElement('multiselect', 'storage_acl', array(
            'label'    => $this->translate->_('Storage ACL'),
            'class' => 'ui-select',
            'size' => 10
        ));
/*        foreach( $wbStorageACL as $v ) {
            $storage_acl->addMultiOptions(array( $v->id => $v->name . ' => ' . $v->description ));
        }*/
        unset ($table);

        /*
         * submit button
         */
        $submit = new Zend_Form_Element_Submit('submit',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok1',
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Submit Form')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $webacula_resources,
            $submit
        ));
    }



}