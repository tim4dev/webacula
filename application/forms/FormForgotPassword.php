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

class FormForgotPassword extends Zend_Form
{

	protected $translate;
	public  $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'



    public function init()
    {
        Zend_Loader::loadClass('Zend_Validate_Regex');
        Zend_Loader::loadClass('Zend_Validate_EmailAddress');
    	// translate
    	$this->translate = Zend_Registry::get('translate');
        Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        $this->setMethod('post');
        $this->setDecorators(array(
            array('ViewScript', array('viewScript' => 'forgot-password.phtml'))
        ));        
        /*
         * username
         */
        $login = $this->createElement('text', 'login', array(
            'decorators' => $this->elDecorators,
            'required'   => true,
            'label' => $this->translate->_('Username'),
            'size' => 25,
            'maxlength' => 50
            ));
        $login->addDecorator('FormElements',
            array('tag'=>'div','style'=>'width:10em; background-color:#E0F0FF;'));

        $login_validator = new Zend_Validate_Regex('/^[a-z0-9\-_@\.]+$/i');
        $login_validator->setMessage( $this->translate->_(
            'Login characters incorrect. Allowed: alphabetical characters, digits, and "- . _ @" characters.'
        ));
        $login->addValidator($login_validator)
              ->addValidator('stringLength', false, array(1, 50))
              ->setRequired(true);
        /*
         * email
         */
        $email = $this->createElement('text', 'email', array(
            'decorators' => $this->elDecorators,
            'required'   => true,
            'label' => $this->translate->_('Email'),
            'size' => 25,
            'maxlength' => 50
            ));
        $email->addDecorator('FormElements',
            array('tag'=>'div','style'=>'width:10em; background-color:#E0F0FF;'));
        $email_validator = new Zend_Validate_Regex('/^(.+)@([^@]+)$/');
        // $email_validator = new Zend_Validate_EmailAddress();
        $email_validator->setMessage( $this->translate->_('Email address incorrect.'));
        $email->addValidator($email_validator)
              ->setRequired(true);
        /*
         * submit
         */
        $submit = $this->createElement('submit', 'submit', array(
            'decorators' => array('ViewHelper', 'Errors'),
            'class' => 'forgot-btn',
            'id'    => 'submit',
            'label' => $this->translate->_('Submit new password')
        ));
        /*
         * create captcha
         */
        $captcha = $this->createElement('captcha', 'captcha', array(
            'label'      => $this->translate->_('Type the characters:'),
            'captcha'    => array(
                'captcha' => 'Figlet',
                'wordLen' => 5,
                'timeout' => 120
                )
        ));

        // And finally add some CSRF protection
        $csrf = $this->createElement('hash', 'csrf', array(
            'ignore' => true,
        ));

        // add elements to form
        $this->addElement($login)
            ->addElement($email)
            ->addElement($submit)
            ->addElement($captcha)
            ->addElement($csrf);
    }

}

