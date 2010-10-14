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

    const MAX_LOGIN_ATTEMPT = 2;
	public  $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'



    public function init()
    {
        $defNamespace = new Zend_Session_Namespace('Default');
    	$use_captcha = ($defNamespace->numLoginFails >= self::MAX_LOGIN_ATTEMPT) ? TRUE : FALSE;

        $this->setMethod('post');
        $this->setDecorators(array(
            array('ViewScript', array('viewScript' => 'login.phtml'))
        ));

        // Создание и конфигурирование элемента username
        $login = $this->createElement('text', 'login', array(
            'decorators' => $this->elDecorators,
            'required'   => true,
            'label' => 'Username',
            'size' => 25,
            'maxlength' => 50
            ));
        $login->addDecorator('FormElements',
            array('tag'=>'div','style'=>'width:10em; background-color:#E0F0FF;'));

        $login->addValidator('alnum')
              ->addValidator('regex', false, array('/^[a-z,0-9]+/'))
              ->addValidator('stringLength', false, array(2, 20))
              ->setRequired(true);

        // Создание и конфигурирование элемента password
        $password = $this->createElement('password', 'pwd', array(
            'decorators' => $this->elDecorators,
            'required'   => true,
            'label' => 'Password',
            'size' => 25,
            'maxlength' => 50
        ));
        $password->addValidator('StringLength', false, array(1, 50))
                 ->setRequired(true);

        $checkbox = $this->createElement('checkbox', 'rememberme', array(
            'decorators' => $this->elDecorators,
            'label' => 'Remember me',
            'checked'  => 1
        ));

        $submit = $this->createElement('submit', 'submit', array(
            'decorators' => array('ViewHelper', 'Errors'),
            'class' => 'login-btn',
            'id'    => 'submit',
            'label' => 'Log In'
        ));

        // Добавление элементов в форму:
        $this->addElement($login)
            ->addElement($password)
            ->addElement($checkbox)
            ->addElement($submit);

        if ($use_captcha)   {
            // Add a captcha
            $captcha = $this->createElement('captcha', 'captcha', array(
                'label'      => 'Введите указанные символы:',
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

            // Добавление капчи в форму
            $this->addElement($captcha)
                ->addElement($csrf);
        }
    }

}

