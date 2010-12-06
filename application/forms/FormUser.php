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


class FormUser extends Zend_Form
{

    protected $translate;
    protected $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'
    protected $_action;


    /**
     * @param <type> $options
     * @param <type> $userid
     * @param <type> $action update | add
     */
    public function __construct($options = null, $action = 'update') {
        // The init() method is called inside parent::__construct()
        $this->_action = $action;
        parent::__construct($options);
    }


    public function init()
    {
        Zend_Loader::loadClass('Zend_Validate_Regex');
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        /*
         * hidden fields
         */        
        $user_id = $this->addElement('hidden', 'user_id', array(
            'decorators' => $this->elDecorators
        ));
        $action_id = $this->addElement('hidden', 'action_id', array(
            'decorators' => $this->elDecorators
        ));
        /*
         * Login
         */
        $login = $this->createElement('text', 'login', array(
            'label'     => $this->translate->_('Login').'*',
            'required'  => true,
            'size'      => 30,
            'maxlength' => 50
        ));
        $login_validator = new Zend_Validate_Regex('/^[a-zA-Z0-9_]+$/');
        $login_validator->setMessage( $this->translate->_('Login incorrect. Login contains only english alphabetical characters, digits and underscore.'));
        $login->addValidator('StringLength', false, array(2, 50))
              ->addValidator($login_validator)
              ->setRequired(true);
        /*
         * Password
         */
        $pwd_label = $this->translate->_('Password');
        if ($this->_action != 'update')
                $pwd_label .= '*';
        $pwd = $this->createElement('password', 'pwd', array(
            'label' => $pwd_label,
            'size' => 25,
            'maxlength' => 50
        ));
        $pwd->addValidator('StringLength', false, array(1, 50));
        if ($this->_action != 'update')
            $pwd->setRequired(true);
        /*
         * Name
         */
        $name = $this->createElement('text', 'name', array(
            'label'     => $this->translate->_('Name'),
            'required'  => false,
            'size'      => 40,
            'maxlength' => 150
        ));
        $name->addValidator('StringLength', false, array(2, 150));
        /*
         * Email
         */
        $email = $this->createElement('text', 'email', array(
            'label'     => $this->translate->_('Email'),
            'required'  => false,
            'size'      => 30,
            'maxlength' => 50
        ));
        $email_validator = new Zend_Validate_Regex('/^(.+)@([^@]+)$/');
        $email_validator->setMessage( $this->translate->_('Email incorrect.'));
        $email->addValidator('StringLength', false, array(3, 50))
              ->addValidator($email_validator);
        /*
         *  active
         */
        $active = $this->createElement('checkbox', 'active', array(
            'label' => $this->translate->_('Active'),
            'checked'  => 1
        ));
        /*
         * Role id
         */       
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $rows  = $table->fetchAll(null, 'id');
        // create element
        $role_id = $this->createElement('select', 'role_id', array(
            'label'    => $this->translate->_('Role').'*',
            'class' => 'ui-select',
            'size' => 10
        ));
        foreach( $rows as $v) {
            $role_id->addMultiOption( $v['id'], $v['name'] );
        }
        $role_id->setRequired(true);
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
            $login,
            $pwd,
            $name,
            $email,
            $active,
            $role_id,
            $action_id,
            $submit,
            $reset
        ));
    }



}