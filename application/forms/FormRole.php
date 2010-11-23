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
require_once 'Zend/Form/Element/Reset.php';


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
        /*
         * hidden fields
         */
        $action_id = $this->addElement('hidden', 'action_id', array(
            'decorators' => $this->elDecorators
        ));
        $role_id = $this->addElement('hidden', 'role_id', array(
            'decorators' => $this->elDecorators
        ));
        /*
         * Order role
         */
        $order = $this->createElement('text', 'order_role', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Order'),
            'required'  => true,
            'size'      => 3,
            'maxlength' => 5
        ));
        $order->addValidator('Int');
        $order->addValidator('NotEmpty');
        /*
         * Name role
         */
        $name = $this->createElement('text', 'role_name', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Name'),
            'required'  => true,
            'size'      => 30,
            'maxlength' => 50
        ));
        $name->addValidator('StringLength', false, array(2, 50));
        $name->addValidator('NotEmpty');
        /*
         * Description role
         */
        $description = $this->createElement('textarea', 'description_role', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Description'),
            'required'  => true,
            'cols' => 50,
            'rows' => 3
        ));
        $description->addValidator('NotEmpty');
        /*
         * Inherited role id
         */       
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $rows  = $table->fetchAll(null, 'id');
        // create element
        $inherit_id = $this->createElement('select', 'inherit_id', array(
            'label'    => $this->translate->_('Inherited role'),
            'class' => 'ui-select',
            'size' => 10
        ));
        $inherit_id->addMultiOption('', '');
        foreach( $rows as $v) {
            $inherit_id->addMultiOption( $v['id'], $v['name'] );
        }
        unset ($table);
        /*
         * submit button
         */
        $submit = new Zend_Form_Element_Submit('submit',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok_'.__CLASS__,
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Submit Form')
        ));
        /*
         * reset button
         */
        $reset = new Zend_Form_Element_Reset('reset',array(
            'decorators' => $this->elDecorators,
            'id'    => 'reset_'.__CLASS__,
            'label' => $this->translate->_('Cancel')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $order,
            $name,
            $description,
            $inherit_id,
            $submit,
            $reset
        ));
    }



}