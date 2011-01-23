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

class FormLogin extends Zend_Form
{

	protected $translate;
    const MAX_LOGIN_ATTEMPT = 3;
	public  $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'



    public function init()
    {
        Zend_Loader::loadClass('Zend_Validate_Regex');
    	// translate
    	$this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // login attempt
        $defNamespace = new Zend_Session_Namespace('Default');
    	$use_captcha = ($defNamespace->numLoginFails >= self::MAX_LOGIN_ATTEMPT) ? TRUE : FALSE;

        $this->setMethod('post');
        
        // username
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

        // password
        $password = $this->createElement('password', 'pwd', array(
            'decorators' => $this->elDecorators,
            'required'   => true,
            'label' => $this->translate->_('Password'),
            'size' => 25,
            'maxlength' => 50
        ));
        $password->addValidator('StringLength', false, array(1, 50))
                 ->setRequired(true);

        // remember me
        $checkbox = $this->createElement('checkbox', 'rememberme', array(
            'decorators' => $this->elDecorators,
            'label' => $this->translate->_('Remember me'),
            'checked'  => 1
        ));

        // login
        $submit = $this->createElement('submit', 'submit', array(
            'decorators' => array('ViewHelper', 'Errors'),
            'class' => 'login-btn',
            'id'    => 'submit',
            'label' => $this->translate->_('Log In')
        ));

        // add elements to form
        $this->addElement($login)
            ->addElement($password)
            ->addElement($checkbox)
            ->addElement($submit);

        if ($use_captcha)   {
            // create captcha
            $captcha = $this->createElement('captcha', 'captcha', array(
                'label'      => $this->translate->_('Type the characters:'),
                'captcha'    => array(
                    'captcha' => 'Figlet',
                    'wordLen' => 3,
                    'timeout' => 120
                    )
            ));

            // And finally add some CSRF protection
            $csrf = $this->createElement('hash', 'csrf', array(
                'ignore' => true,
            ));

            // add captcha to form
            $this->addElement($captcha)
                ->addElement($csrf);
        }
    }

}

