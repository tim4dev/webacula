<?php
/**
 * Copyright 2010 Yuriy Timofeev tim4dev@gmail.com
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


class FormBaculaCommandACL extends Zend_Form
{

    protected $translate;
    protected $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'
	protected $_action_cancel = '';


    public function init()
    {       
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        /*
         * hidden fields
         */
        $role_id = $this->addElement('hidden', 'role_id', array(
            'decorators' => $this->elDecorators
        ));
        $role_name = $this->addElement('hidden', 'role_name', array(
            'decorators' => $this->elDecorators
        ));
        $acl = $this->addElement('hidden', 'acl', array(
            'decorators' => $this->elDecorators
        ));
        /*
         * Bacula resources
         */
        Zend_Loader::loadClass('WbDtCommands');
        $table = new WbDtCommands();
        $wbDtCommands = $table->fetchAll(null, 'id');
        // create element
        $bacula_commands = $this->createElement('multiselect', 'bacula_commands', array(
            'label'    => $this->translate->_('Bacula commands'),
            'class' => 'ui-select',
            'size' => 18
        ));
        foreach( $wbDtCommands as $v ) {
            $bacula_commands->addMultiOptions(array( $v->id => $v->name . ' => ' . $v->description ));
        }
        unset ($table);
        /*
         * submit button
         */
        $submit_button = new Zend_Form_Element_Submit('submit_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok_'.__CLASS__,
            'class' => 'ui-button ui-widget ui-corner-all',
            'label' => $this->translate->_('Submit Form')
        ));
        /*
         * cancel button
         */
        $cancel_button = new Zend_Form_Element_Submit('cancel_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'cancel_'.__CLASS__,
            'class' => 'ui-button ui-widget ui-corner-all',
            'label' => $this->translate->_('Cancel')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $bacula_commands,
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
